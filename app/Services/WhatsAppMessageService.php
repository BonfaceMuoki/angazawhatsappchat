<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppMessageService
{
    private function getConfig(): array
    {
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $token = config('services.whatsapp.access_token');

        return [$phoneNumberId, $token];
    }

    private function toE164(string $phone): string
    {
        return preg_replace('/\D/', '', $phone);
    }

    private function post(array $payload): ?array
    {
        [$phoneNumberId, $token] = $this->getConfig();

        if (!$phoneNumberId || !$token) {
            Log::warning('WhatsAppMessageService: missing WhatsApp config');
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
            $data = $response->json();
            $fbMessage = $data['error']['message'] ?? $response->body();
            Log::warning('WhatsAppMessageService: send failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return ['_error' => $fbMessage];
        }

        return $response->json();
    }

    /**
     * Send a plain text message.
     */
    public function sendText(string $phone, string $message): ?array
    {
        return $this->post([
            'to' => $phone,
            'type' => 'text',
            'text' => ['body' => $message],
        ]);
    }

    /**
     * Send interactive reply buttons (max 3).
     * $buttons: array of ['id' => 'option_id', 'title' => 'Option label']
     */
    public function sendButtons(string $phone, string $question, array $buttons): ?array
    {
        $buttons = array_slice($buttons, 0, 3);

        $actionButtons = [];
        foreach ($buttons as $btn) {
            $actionButtons[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => $btn['id'],
                    'title' => $btn['title'],
                ],
            ];
        }

        return $this->post([
            'to' => $phone,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $question],
                'action' => ['buttons' => $actionButtons],
            ],
        ]);
    }

    /**
     * Send interactive list (up to 10 options across sections).
     * $sections: array of ['title' => 'Section name', 'rows' => [['id' => '...', 'title' => '...'], ...]]
     */
    public function sendList(string $phone, string $question, string $buttonText, array $sections): ?array
    {
        return $this->post([
            'to' => $phone,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'list',
                'body' => ['text' => $question],
                'action' => [
                    'button' => $buttonText,
                    'sections' => $sections,
                ],
            ],
        ]);
    }
}
