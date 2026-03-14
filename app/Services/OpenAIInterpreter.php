<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIInterpreter
{
    private const MODEL = 'gpt-4.1-mini';

    private const ALLOWED = [
        'role' => ['student', 'graduate', 'working_professional', 'parent', 'unknown'],
        'program_interest' => ['software_engineering', 'data_analytics', 'cloud_computing', 'cybersecurity', 'unsure', 'unknown'],
        'commitment_level' => ['high_commitment', 'medium_commitment', 'exploring', 'unknown'],
        'experience_level' => ['programming', 'data_analysis', 'cloud_platforms', 'none', 'unknown'],
        'budget_status' => ['yes', 'installment', 'not_currently', 'unknown'],
        'conversion_action' => ['apply_now', 'attend_info_session', 'undecided', 'unknown'],
        'education_comfort' => ['yes', 'tell_me_more', 'unknown'],
    ];

    private const SYSTEM_PROMPT = <<<PROMPT
You are an intent classifier for a WhatsApp admissions assistant.

You must classify the user message into the allowed funnel categories.

Never generate explanations.
Never invent values.
Return ONLY valid JSON.

Allowed classifications:

role:
student
graduate
working_professional
parent

program_interest:
software_engineering
data_analytics
cloud_computing
cybersecurity
unsure

commitment_level:
high_commitment
medium_commitment
exploring

experience_level:
programming
data_analysis
cloud_platforms
none

budget_status:
yes
installment
not_currently

conversion_action:
apply_now
attend_info_session
undecided

education_comfort:
yes
tell_me_more

If unsure return "unknown".

Return format:

{"role":"...","program_interest":"...","commitment_level":"...","experience_level":"...","budget_status":"...","conversion_action":"...","education_comfort":"...","confidence":0.0}
PROMPT;

    /**
     * Interpret user message and classify into funnel categories.
     * Returns validated structured data or null if disabled/failed.
     */
    public function interpret(string $message): ?array
    {
        if (!Setting::isEnabled('openai_enabled')) {
            return null;
        }

        $apiKey = config('services.openai.api_key');
        if (!$apiKey) {
            Log::warning('OpenAIInterpreter: missing API key');
            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(15)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => self::MODEL,
                    'messages' => [
                        ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                        ['role' => 'user', 'content' => $message],
                    ],
                    'temperature' => 0.1,
                ]);

            if (!$response->successful()) {
                Log::warning('OpenAIInterpreter: API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $body = $response->json();
            $content = $body['choices'][0]['message']['content'] ?? null;
            if (!$content || !is_string($content)) {
                Log::warning('OpenAIInterpreter: empty or non-string content', ['body' => $body]);
                return null;
            }

            $content = trim($content);
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
            $content = preg_replace('/\s*```\s*$/', '', $content);
            $content = $this->extractJson($content);
            $parsed = json_decode($content, true);
            if (!is_array($parsed)) {
                Log::warning('OpenAIInterpreter: invalid JSON', ['raw' => $content]);
                return null;
            }

            $validated = $this->validate($parsed);

            Log::info('AI classification:', [
                'message' => $message,
                'parsed' => $validated,
            ]);

            return $validated;
        } catch (\Throwable $e) {
            Log::warning('OpenAIInterpreter: exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Validate and sanitize parsed AI output against allowed lists.
     */
    private function validate(array $parsed): array
    {
        $out = [];
        foreach (self::ALLOWED as $key => $allowed) {
            $value = $parsed[$key] ?? 'unknown';
            if (is_string($value)) {
                $value = strtolower(trim($value));
            }
            $out[$key] = in_array($value, $allowed, true) ? $value : 'unknown';
        }
        $out['confidence'] = isset($parsed['confidence'])
            ? (float) $parsed['confidence']
            : 0.0;
        $out['confidence'] = max(0, min(1, $out['confidence']));
        return $out;
    }

    private function extractJson(string $content): string
    {
        $content = trim($content);
        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $content, $m)) {
            return $m[0];
        }
        return $content;
    }

    /**
     * Map AI classification field value to conversation flow option ID for the given stage.
     */
    public function classificationToOptionId(string $stage, array $classification): ?string
    {
        $map = [
            'entry' => [
                'student' => 'student',
                'graduate' => 'graduate',
                'working_professional' => 'professional',
                'parent' => 'parent',
            ],
            'role' => [
                'software_engineering' => 'software_engineering',
                'data_analytics' => 'data_analytics',
                'cloud_computing' => 'cloud_computing',
                'cybersecurity' => 'cybersecurity',
                'unsure' => 'not_sure',
            ],
            'commitment' => [
                'high_commitment' => 'commit_high',
                'medium_commitment' => 'commit_medium',
                'exploring' => 'exploring',
            ],
            'experience' => [
                'programming' => 'programming',
                'data_analysis' => 'data_analysis',
                'cloud_platforms' => 'cloud_platforms',
                'none' => 'none',
            ],
            'pricing' => [
                'yes' => 'yes',
                'installment' => 'installment',
                'not_currently' => 'not_currently',
            ],
            'education' => [
                'yes' => 'yes',
                'tell_me_more' => 'tell_me_more',
            ],
            'conversion' => [
                'apply_now' => 'apply_now',
                'attend_info_session' => 'attend_info_session',
            ],
        ];

        $stageMap = $map[$stage] ?? null;
        if (!$stageMap) {
            return null;
        }

        $field = $this->stageToField($stage);
        $value = $classification[$field] ?? 'unknown';
        return $stageMap[$value] ?? null;
    }

    private function stageToField(string $stage): string
    {
        $fields = [
            'entry' => 'role',
            'role' => 'program_interest',
            'commitment' => 'commitment_level',
            'experience' => 'experience_level',
            'pricing' => 'budget_status',
            'education' => 'education_comfort',
            'conversion' => 'conversion_action',
        ];
        return $fields[$stage] ?? 'unknown';
    }
}
