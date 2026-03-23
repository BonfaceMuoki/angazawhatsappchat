<?php

namespace App\Services;

use App\Jobs\SendPushNotificationJob;
use App\Models\BotEdge;
use App\Models\BotFlow;
use App\Models\BotNode;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BotEngineService
{
    public function __construct(
        private WhatsAppService $whatsApp,
        private AIIntentService $aiIntent
    ) {}

    public function handleIncomingMessage(string $phone, string $text, ?string $wamid = null): void
    {
        $this->saveMessage($phone, Message::DIRECTION_INCOMING, $text, null);

        $dashboardUrl = rtrim(config('app.dashboard_url', config('app.url')), '/');
        SendPushNotificationJob::dispatch($phone, $text, $dashboardUrl . '/chats/' . rawurlencode($phone));

        $conversation = Conversation::firstOrCreate(
            ['phone' => $phone],
            ['stage' => 'entry', 'bot_active' => true]
        );

        $conversation->update(['last_user_message_at' => now()]);

        // Human takeover: do not send automated responses
        if ($conversation->bot_active === false) {
            return;
        }

        // No flow yet: router or start single flow
        if ($conversation->flow_id === null) {
            $this->handleNoFlow($conversation, $phone, $text);
            return;
        }

        // In flow: resolve to edge and advance
        $conversation->load('currentNode.outgoingEdges');
        $node = $conversation->currentNode;
        if (!$node) {
            Log::warning('BotEngineService: conversation has flow_id but no current_node_id', ['phone' => $phone]);
            $flow = $conversation->flow;
            if ($flow?->entry_node_id) {
                $conversation->update(['current_node_id' => $flow->entry_node_id, 'stage' => $flow->entryNode?->node_key ?? 'entry']);
                $this->sendNodeMessage($conversation, $flow->entryNode);
            }
            return;
        }

        $edges = $node->outgoingEdges;
        $matchedEdge = $this->resolveToEdge($text, $edges);

        if ($matchedEdge === null) {
            // Re-send the current question + options so the user sees what to choose (e.g. after "Hi")
            if ($edges->isNotEmpty()) {
                $this->sendNodeMessage($conversation, $node);
                return;
            }
            $this->sendFallback($phone);
            return;
        }

        $targetNode = $matchedEdge->targetNode;
        $conversation->update([
            'current_node_id' => $targetNode->id,
            'stage' => $targetNode->node_key,
            'last_bot_message_at' => now(),
        ]);
        $this->sendNodeMessage($conversation, $targetNode);
    }

    private function handleNoFlow(Conversation $conversation, string $phone, string $text): void
    {
        $flows = BotFlow::where('is_active', true)->orderBy('display_order')->get();

        if ($flows->isEmpty()) {
            Log::warning('BotEngineService: no active flows');
            return;
        }

        if ($flows->count() === 1) {
            $flow = $flows->first();
            $entryNode = $flow->entryNode;
            if (!$entryNode) {
                Log::warning('BotEngineService: flow has no entry node', ['flow_id' => $flow->id]);
                return;
            }
            $conversation->update([
                'flow_id' => $flow->id,
                'current_node_id' => $entryNode->id,
                'stage' => $entryNode->node_key,
            ]);
            $this->sendNodeMessage($conversation, $entryNode);
            return;
        }

        // Router: multiple flows — match reply to a flow
        $routerFlows = $flows->where('show_in_router', true)->values();
        if ($routerFlows->isEmpty()) {
            $routerFlows = $flows;
        }

        $matchedFlow = $routerFlows->first(fn (BotFlow $f) => strtolower(trim($text)) === 'flow_' . $f->id || strtolower(trim($text)) === strtolower($f->name));

        if ($matchedFlow) {
            $entryNode = $matchedFlow->entryNode;
            if (!$entryNode) {
                return;
            }
            $conversation->update([
                'flow_id' => $matchedFlow->id,
                'current_node_id' => $entryNode->id,
                'stage' => $entryNode->node_key,
            ]);
            $this->sendNodeMessage($conversation, $entryNode);
            return;
        }

        // Send router menu (first time or invalid choice)
        $hasSentRouter = Message::where('phone', $phone)->where('direction', Message::DIRECTION_OUTGOING)->exists();
        if (!$hasSentRouter || $routerFlows->count() <= 3) {
            $this->sendRouterMenu($phone, $routerFlows);
        } else {
            $this->sendRouterList($phone, $routerFlows);
        }
    }

    private function sendRouterMenu(string $phone, $flows): void
    {
        $welcome = "Welcome 👋\nHow can we help you today?";
        $buttons = $flows->take(3)->map(fn (BotFlow $f) => ['id' => 'flow_' . $f->id, 'title' => $f->name])->all();
        $response = $this->whatsApp->sendButtonMessage($phone, $welcome, $buttons);
        $wamid = $response !== null ? ($response['messages'][0]['id'] ?? null) : null;
        $this->saveMessage($phone, Message::DIRECTION_OUTGOING, $welcome, $wamid);
    }

    private function sendRouterList(string $phone, $flows): void
    {
        $welcome = "Welcome 👋\nHow can we help you today?";
        $rows = $flows->map(fn (BotFlow $f) => ['id' => 'flow_' . $f->id, 'title' => $f->name])->all();
        $sections = [['title' => 'Select an option', 'rows' => $rows]];
        $response = $this->whatsApp->sendListMessage($phone, $welcome, 'View options', $sections);
        $wamid = $response !== null ? ($response['messages'][0]['id'] ?? null) : null;
        $this->saveMessage($phone, Message::DIRECTION_OUTGOING, $welcome, $wamid);
    }

    private function resolveToEdge(string $text, $edges): ?BotEdge
    {
        $normalized = strtolower(trim($text));
        foreach ($edges as $edge) {
            if (strtolower($edge->option_value) === $normalized) {
                return $edge;
            }
        }

        return $this->aiIntent->resolveToOptionValue($text, $edges);
    }

    /**
     * WhatsApp allows at most 3 reply buttons; list rows are chunked (10 per section).
     * Node type drives UX: list = always list; buttons = buttons if ≤3 options else list; otherwise edge-count heuristic.
     */
    private function sendNodeMessage(Conversation $conversation, BotNode $node): void
    {
        $phone = $conversation->phone;
        $edges = $node->outgoingEdges;

        if ($edges->isEmpty()) {
            $response = $this->whatsApp->sendTextMessage($phone, $node->message);
        } elseif ($this->shouldUseListForOptions($node, $edges)) {
            $sections = $this->edgesToListSections($edges);
            $response = $this->whatsApp->sendListMessage($phone, $node->message, 'View options', $sections);
        } else {
            $buttons = $edges->map(fn (BotEdge $e) => ['option_value' => $e->option_value, 'option_label' => $e->option_label])->all();
            $response = $this->whatsApp->sendButtonMessage($phone, $node->message, $buttons);
        }

        $wamid = $response !== null ? ($response['messages'][0]['id'] ?? null) : null;
        $this->saveMessage($phone, Message::DIRECTION_OUTGOING, $node->message, $wamid, $node->id);
        $conversation->update(['last_bot_message_at' => now()]);
    }

    private function shouldUseListForOptions(BotNode $node, Collection $edges): bool
    {
        $count = $edges->count();

        if ($node->type === 'list') {
            return true;
        }

        if ($node->type === 'buttons') {
            if ($count > 3) {
                Log::info('BotEngineService: using list (WhatsApp max 3 reply buttons)', [
                    'node_id' => $node->id,
                    'node_key' => $node->node_key,
                    'edge_count' => $count,
                ]);
            }

            return $count > 3;
        }

        // text / legacy: same heuristic as before — list when more than 3 options
        return $count > 3;
    }

    /**
     * WhatsApp list: up to 10 rows per section; multiple sections allowed.
     *
     * @param  Collection<int, BotEdge>  $edges
     * @return array<int, array{title: string, rows: array<int, array{id: string, title: string}>}>
     */
    private function edgesToListSections(Collection $edges): array
    {
        $rows = $edges->map(fn (BotEdge $e) => [
            'id' => $e->option_value,
            'title' => $e->option_label,
        ])->values()->all();

        $chunks = array_chunk($rows, 10);
        $sections = [];
        foreach ($chunks as $index => $chunk) {
            $sections[] = [
                'title' => $index === 0 ? 'Select an option' : 'More options',
                'rows' => $chunk,
            ];
        }

        return $sections;
    }

    private function sendFallback(string $phone): void
    {
        $fallback = 'Please choose one of the options above.';
        $response = $this->whatsApp->sendTextMessage($phone, $fallback);
        $wamid = $response !== null ? ($response['messages'][0]['id'] ?? null) : null;
        $this->saveMessage($phone, Message::DIRECTION_OUTGOING, $fallback, $wamid);
    }

    private function saveMessage(string $phone, string $direction, string $body, ?string $wamid = null, ?int $nodeId = null): void
    {
        Message::create([
            'phone' => $phone,
            'direction' => $direction,
            'body' => $body,
            'wamid' => $wamid,
            'node_id' => $nodeId,
        ]);
    }
}
