<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private WhatsAppBotService $botService
    ) {}

    /**
     * Meta webhook verification
     */
    public function verify(Request $request)
    {
        $verifyToken = config('services.whatsapp.verify_token');

        if (
            $request->get('hub_mode') === 'subscribe' &&
            $request->get('hub_verify_token') === $verifyToken
        ) {
            return response($request->get('hub_challenge'), 200);
        }

        return response('Verification failed', 403);
    }

    /**
     * Receive webhook events
     */
    public function handle(Request $request)
    {
        Log::info('WhatsApp webhook POST received', [
            'has_payload' => $request->getContent() !== '',
            'object' => $request->input('object'),
            'entry_count' => count($request->input('entry', [])),
        ]);

        $value = $request->input('entry.0.changes.0.value');
        if (!$value) {
            return response()->json(['status' => 'ok']);
        }

        if (isset($value['statuses'])) {
            return response()->json(['success' => true]);
        }

        if (!isset($value['messages'])) {
            return response()->json(['success' => true]);
        }

        $message = $value['messages'][0];
        $phone = $message['from'] ?? null;
        $wamid = $message['id'] ?? null;
        $text = $this->extractMessageText($message);

        if (!$phone || $text === '') {
            return response()->json(['success' => true]);
        }

        $this->botService->handleIncomingMessage($phone, $text, $wamid);

        return response()->json(['success' => true]);
    }

    /**
     * Extract user response from text message, button reply, or list reply.
     * Returns the selected option ID for interactive messages, or the typed text.
     */
    private function extractMessageText(array $message): string
    {
        if (isset($message['text']['body'])) {
            return trim($message['text']['body']);
        }

        if (isset($message['interactive']['button_reply']['id'])) {
            return $message['interactive']['button_reply']['id'];
        }

        if (isset($message['interactive']['list_reply']['id'])) {
            return $message['interactive']['list_reply']['id'];
        }

        return '';
    }
}
