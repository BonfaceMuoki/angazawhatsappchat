<?php

namespace App\Http\Controllers;

use App\Services\BotEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private BotEngineService $botService
    ) {}

    /**
     * Meta webhook verification (GET).
     *
     * Meta sends dotted query keys: hub.mode, hub.verify_token, hub.challenge
     * (not hub_mode). Callback URL must include the /api prefix, e.g.
     * https://your-domain.com/api/webhook/whatsapp
     */
    public function verify(Request $request)
    {
        // Trim: .env copy/paste often adds trailing newlines/spaces (common 403 cause in Docker).
        $verifyToken = trim((string) config('services.whatsapp.verify_token', ''));

        if ($verifyToken === '') {
            Log::warning('WhatsApp webhook verify: WHATSAPP_VERIFY_TOKEN is empty');

            return response('Verification failed: verify token not configured', 503);
        }

        // Meta sends hub.mode, hub.verify_token, hub.challenge. PHP/Symfony may expose them as
        // hub_mode, hub_verify_token, hub_challenge — resolve each independently (do not only
        // fall back when mode is null, or token can stay null → 403).
        $mode = $request->query('hub.mode') ?? $request->query('hub_mode');
        $token = trim((string) ($request->query('hub.verify_token') ?? $request->query('hub_verify_token') ?? ''));
        $challenge = $request->query('hub.challenge') ?? $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token !== '' && hash_equals($verifyToken, $token) && $challenge !== null && $challenge !== '') {
            return response((string) $challenge, 200)
                ->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        Log::warning('WhatsApp webhook verify: rejected', [
            'mode' => $mode,
            'has_challenge' => $challenge !== null && $challenge !== '',
            'has_token' => $token !== '',
            'token_matches' => $token !== '' && hash_equals($verifyToken, $token),
        ]);

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

        foreach ($value['messages'] as $message) {
            if (! is_array($message)) {
                continue;
            }

            $phone = $message['from'] ?? null;
            $wamid = $message['id'] ?? null;
            $text = $this->extractMessageText($message);

            if (! $phone) {
                Log::warning('WhatsApp webhook: message without from', ['keys' => array_keys($message)]);

                continue;
            }

            if ($text === '') {
                Log::info('WhatsApp webhook: skipped (no extractable text / unsupported type)', [
                    'from' => $phone,
                    'type' => $message['type'] ?? null,
                ]);

                continue;
            }

            $this->botService->handleIncomingMessage($phone, $text, $wamid);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Extract user response from text, interactive replies, or a short placeholder for media.
     * Empty string = nothing to pass to the bot (caller may skip saving).
     */
    private function extractMessageText(array $message): string
    {
        if (isset($message['text']['body'])) {
            return trim($message['text']['body']);
        }

        if (isset($message['interactive']['button_reply'])) {
            $title = trim((string) ($message['interactive']['button_reply']['title'] ?? ''));
            if ($title !== '') {
                return $title;
            }
            if (isset($message['interactive']['button_reply']['id'])) {
                return (string) $message['interactive']['button_reply']['id'];
            }
        }

        if (isset($message['interactive']['list_reply'])) {
            $title = trim((string) ($message['interactive']['list_reply']['title'] ?? ''));
            if ($title !== '') {
                return $title;
            }
            if (isset($message['interactive']['list_reply']['id'])) {
                return (string) $message['interactive']['list_reply']['id'];
            }
        }

        $type = $message['type'] ?? null;

        // Media often has no text body — still register something so the thread shows activity
        if ($type === 'image' && isset($message['image'])) {
            $cap = trim((string) ($message['image']['caption'] ?? ''));

            return $cap !== '' ? $cap : '[image]';
        }

        if ($type === 'document' && isset($message['document'])) {
            $cap = trim((string) ($message['document']['caption'] ?? ''));
            $name = (string) ($message['document']['filename'] ?? 'file');

            return $cap !== '' ? $cap : '[document: '.$name.']';
        }

        if ($type === 'audio') {
            return '[audio]';
        }

        if ($type === 'video' && isset($message['video'])) {
            $cap = trim((string) ($message['video']['caption'] ?? ''));

            return $cap !== '' ? $cap : '[video]';
        }

        if ($type === 'sticker') {
            return '[sticker]';
        }

        if ($type === 'location') {
            return '[location]';
        }

        if ($type === 'contacts') {
            return '[contact shared]';
        }

        if ($type === 'reaction') {
            return '';
        }

        return '';
    }
}
