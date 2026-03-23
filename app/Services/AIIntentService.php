<?php

namespace App\Services;

use App\Models\BotEdge;
use App\Models\BotSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI assists only with intent detection and free-text interpretation.
 * Runs only when bot_settings.ai_enabled is true and ai_mode allows it.
 * Does NOT control conversation flow — only suggests which edge option_value matches the user message.
 */
class AIIntentService
{
    private const MODEL = 'gpt-4.1-mini';

    public function resolveToOptionValue(string $userMessage, Collection $edges): ?string
    {
        if (!BotSetting::isEnabled('ai_enabled')) {
            return null;
        }

        $mode = BotSetting::getValue('ai_mode', 'off');
        if (!in_array($mode, ['intent_detection', 'full'], true)) {
            return null;
        }

        $apiKey = config('services.openai.api_key');
        if (!$apiKey) {
            Log::warning('AIIntentService: missing OpenAI API key');
            return null;
        }

        $optionsList = $edges->map(fn (BotEdge $e) => $e->option_value . ' = "' . $e->option_label . '"')->join("\n");

        $systemPrompt = <<<PROMPT
You are an intent classifier. Given a user message and a list of allowed options, return the single option_value that best matches the user's intent.

Rules:
- Return ONLY the exact option_value (e.g. "yes", "apply_now"). No explanation.
- If the message clearly matches one option, return that option_value.
- If the message is ambiguous or matches none, return the word "unknown".
- Options (option_value = "label"):
{$optionsList}
PROMPT;

        try {
            $response = Http::withToken($apiKey)
                ->timeout(15)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => self::MODEL,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    'temperature' => 0.1,
                ]);

            if (!$response->successful()) {
                Log::warning('AIIntentService: API error', ['status' => $response->status()]);
                return null;
            }

            $content = trim($response->json('choices.0.message.content') ?? '');
            if ($content === '' || strtolower($content) === 'unknown') {
                return null;
            }

            $optionValues = $edges->pluck('option_value')->all();
            if (in_array($content, $optionValues, true)) {
                return $content;
            }

            // Normalize: lowercase, strip quotes
            $normalized = strtolower(trim($content, '"\''));
            foreach ($optionValues as $v) {
                if (strtolower($v) === $normalized) {
                    return $v;
                }
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('AIIntentService: exception', ['message' => $e->getMessage()]);
            return null;
        }
    }
}
