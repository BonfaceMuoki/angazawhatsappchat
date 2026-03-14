<?php

namespace App\Services;

use App\Jobs\SendPushNotificationJob;
use App\Models\Conversation;
use App\Models\ConversationFlow;
use App\Models\Message;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class WhatsAppBotService
{
    public function __construct(
        private OpenAIInterpreter $openAIInterpreter,
        private WhatsAppMessageService $messageService
    ) {}

    public function handleIncomingMessage(string $phone, string $text, ?string $wamid = null): void
    {
        $this->saveMessage($phone, Message::DIRECTION_INCOMING, $text, $wamid);

        $dashboardUrl = rtrim(config('app.dashboard_url', config('app.url')), '/');
        SendPushNotificationJob::dispatch($phone, $text, $dashboardUrl . '/chats/' . rawurlencode($phone));

        $conversation = Conversation::firstOrCreate(
            ['phone' => $phone],
            ['stage' => 'entry']
        );

        $flow = $conversation->getCurrentFlow();
        if (!$flow) {
            Log::warning('WhatsAppBotService: no flow for stage', ['stage' => $conversation->stage]);
            return;
        }

        $resolvedOptionId = $this->resolveOptionId($conversation->stage, $text, $flow);

        if ($resolvedOptionId === null) {
            $hasBeenSentOptions = Message::where('phone', $phone)
                ->where('direction', Message::DIRECTION_OUTGOING)
                ->exists();

            if (!$hasBeenSentOptions) {
                // First contact: send the current stage question (list/buttons) so user has something to choose
                $this->sendNextQuestion($conversation);
                return;
            }

            $fallback = 'Please choose one of the options above.';
            $response = $this->messageService->sendText($phone, $fallback);
            $wamid = $response !== null ? ($response['messages'][0]['id'] ?? null) : null;
            $this->saveMessage($phone, Message::DIRECTION_OUTGOING, $fallback, $wamid);
            return;
        }

        $this->advanceStage($conversation);
        $this->sendNextQuestion($conversation);
    }

    /**
     * Resolve user input to a flow option ID: exact button/list match, or AI classification.
     */
    private function resolveOptionId(string $stage, string $text, ConversationFlow $flow): ?string
    {
        $optionIds = $this->getOptionIds($flow);
        $normalized = strtolower(trim($text));

        if (in_array($normalized, $optionIds, true)) {
            return $normalized;
        }

        if (!Setting::isEnabled('openai_enabled')) {
            Log::debug('WhatsAppBotService: openai_enabled is off, skipping AI');
            return null;
        }

        Log::info('WhatsAppBotService: resolving via AI', ['stage' => $stage, 'text' => $text]);

        $classification = $this->openAIInterpreter->interpret($text);
        if ($classification === null) {
            Log::warning('WhatsAppBotService: AI returned null', ['stage' => $stage]);
            return null;
        }

        $mapped = $this->openAIInterpreter->classificationToOptionId($stage, $classification);
        if ($mapped === null || !in_array($mapped, $optionIds, true)) {
            Log::info('WhatsAppBotService: AI mapping not in options', [
                'stage' => $stage,
                'mapped' => $mapped,
                'optionIds' => $optionIds,
            ]);
            return null;
        }

        return $mapped;
    }

    private function getOptionIds(ConversationFlow $flow): array
    {
        $options = $flow->options ?? [];
        $ids = array_map(fn ($o) => $o['id'] ?? '', $options);
        return array_values(array_filter($ids));
    }

    public function getCurrentStage(string $phone): string
    {
        $conversation = Conversation::where('phone', $phone)->first();
        return $conversation ? $conversation->stage : 'entry';
    }

    /**
     * Reset a user's conversation so they start the flow from the beginning.
     * Optionally clear message history for that phone.
     */
    public function resetConversation(string $phone, bool $clearMessages = false): bool
    {
        $conversation = Conversation::where('phone', $phone)->first();
        if (!$conversation) {
            return true;
        }

        $conversation->update(['stage' => 'entry']);

        if ($clearMessages) {
            Message::where('phone', $phone)->delete();
        }

        return true;
    }

    public function advanceStage(Conversation $conversation): void
    {
        $flow = $conversation->getCurrentFlow();
        if ($flow && $flow->next_stage) {
            $conversation->update(['stage' => $flow->next_stage]);
        }
    }

    public function sendNextQuestion(Conversation $conversation): void
    {
        $flow = $conversation->getCurrentFlow();
        if (!$flow) {
            Log::warning('WhatsAppBotService: no flow for stage', ['stage' => $conversation->stage]);
            return;
        }

        $phone = $conversation->phone;
        $question = $flow->question;
        $options = $flow->options ?? [];

        if (count($options) > 3) {
            $sections = [['title' => 'Select an option', 'rows' => $options]];
            $response = $this->messageService->sendList($phone, $question, 'View options', $sections);
        } elseif (count($options) > 0) {
            $response = $this->messageService->sendButtons($phone, $question, $options);
        } else {
            $response = $this->messageService->sendText($phone, $question);
        }

        $wamid = $response !== null ? ($response['messages'][0]['id'] ?? null) : null;
        $this->saveMessage($phone, Message::DIRECTION_OUTGOING, $question, $wamid);
    }

    public function sendMessage(string $phone, string $body, ?string $wamid = null, ?string $status = null): bool
    {
        $response = $this->messageService->sendText($phone, $body);
        if ($response === null) {
            return false;
        }
        $outgoingWamid = $response['messages'][0]['id'] ?? null;
        $this->saveMessage($phone, Message::DIRECTION_OUTGOING, $body, $outgoingWamid, $status);
        return true;
    }

    private function saveMessage(
        string $phone,
        string $direction,
        string $body,
        ?string $wamid = null,
        ?string $status = null
    ): void {
        Message::create([
            'phone' => $phone,
            'direction' => $direction,
            'body' => $body,
            'wamid' => $wamid,
            'status' => $status,
        ]);
    }
}
