<?php

namespace App\Jobs;

use App\Models\PushSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $phone,
        public string $bodyPreview,
        public string $chatUrl
    ) {}

    public function handle(): void
    {
        if (!class_exists(\Minishlink\WebPush\WebPush::class)) {
            Log::info('Web Push skipped: minishlink/web-push not installed. Run: composer require minishlink/web-push');
            return;
        }

        $vapidPublic = config('services.webpush.vapid_public');
        $vapidPrivate = config('services.webpush.vapid_private');
        if (!$vapidPublic || !$vapidPrivate) {
            Log::info('Web Push skipped: VAPID keys not set. Add VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY to .env and run php artisan webpush:generate-keys');
            return;
        }

        $subscriptions = PushSubscription::all();
        if ($subscriptions->isEmpty()) {
            Log::info('Web Push skipped: no subscriptions. Open the dashboard Chats page and click "Enable push notifications", then allow when prompted.');
            return;
        }

        Log::info('Web Push sending to ' . $subscriptions->count() . ' subscription(s)', ['phone' => $this->phone]);

        $payload = json_encode([
            'title' => 'New message',
            'body' => mb_substr($this->bodyPreview, 0, 100),
            'url' => $this->chatUrl,
        ]);

        try {
            $auth = [
                'VAPID' => [
                    'subject' => config('app.url', 'mailto:admin@localhost'),
                    'publicKey' => $vapidPublic,
                    'privateKey' => $vapidPrivate,
                ],
            ];
            $webPush = new \Minishlink\WebPush\WebPush($auth);

            foreach ($subscriptions as $sub) {
                try {
                    $subscription = \Minishlink\WebPush\Subscription::create([
                        'endpoint' => $sub->endpoint,
                        'keys' => [
                            'p256dh' => $sub->public_key,
                            'auth' => $sub->auth_token,
                        ],
                    ]);
                    $webPush->sendOneNotification($subscription, $payload);
                } catch (\Throwable $e) {
                    Log::warning('Web Push send failed', ['endpoint' => $sub->endpoint, 'error' => $e->getMessage()]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Web Push error', ['message' => $e->getMessage()]);
        }
    }
}
