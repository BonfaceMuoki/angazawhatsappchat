<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Cloud API — send text, button, and list messages.
 * Node type determines which method to use.
 */
class WhatsAppService
{
    private function getConfig(): array
    {
        return [
            config('services.whatsapp.phone_number_id'),
            config('services.whatsapp.access_token'),
        ];
    }

    private function toE164(string $phone): string
    {
        return preg_replace('/\D/', '', $phone);
    }

    private function post(array $payload): ?array
    {
        [$phoneNumberId, $token] = $this->getConfig();
        if (!$phoneNumberId || !$token) {
            Log::warning('WhatsAppService: missing config');
            return null;
        }

        $body = [
            'messaging_product' => 'whatsapp',
            'to' => $this->toE164($payload['to']),
            'type' => $payload['type'],
        ];
        if (isset($payload['text'])) {
            $body['text'] = $payload['text'];
        }
        if (isset($payload['interactive'])) {
            $body['interactive'] = $payload['interactive'];
        }

        $response = Http::withToken($token)->post(
            'https://graph.facebook.com/v22.0/' . $phoneNumberId . '/messages',
            $body
        );

        if (!$response->successful()) {
            Log::warning('WhatsAppService: send failed', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        }

        return $response->json();
    }

    public function sendTextMessage(string $phone, string $message): ?array
    {
        return $this->post([
            'to' => $phone,
            'type' => 'text',
            'text' => ['body' => $message],
        ]);
    }

    public function sendButtonMessage(string $phone, string $bodyText, array $buttons): ?array
    {
        $buttons = array_slice($buttons, 0, 3);
        $actionButtons = [];
        foreach ($buttons as $btn) {
            $actionButtons[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => $btn['id'] ?? $btn['option_value'],
                    'title' => $btn['title'] ?? $btn['option_label'],
                ],
            ];
        }
        return $this->post([
            'to' => $phone,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $bodyText],
                'action' => ['buttons' => $actionButtons],
            ],
        ]);
    }

    /** WhatsApp list row title max length */
    private const LIST_ROW_TITLE_MAX = 24;

    public function sendListMessage(string $phone, string $bodyText, string $buttonText, array $sections): ?array
    {
        $sections = array_map(function (array $section) {
            if (isset($section['rows'])) {
                $section['rows'] = array_map(function (array $row) {
                    if (isset($row['title']) && mb_strlen($row['title']) > self::LIST_ROW_TITLE_MAX) {
                        $row['title'] = mb_substr($row['title'], 0, self::LIST_ROW_TITLE_MAX);
                    }
                    return $row;
                }, $section['rows']);
            }
            return $section;
        }, $sections);

        return $this->post([
            'to' => $phone,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'list',
                'body' => ['text' => $bodyText],
                'action' => [
                    'button' => $buttonText,
                    'sections' => $sections,
                ],
            ],
        ]);
    }
}
