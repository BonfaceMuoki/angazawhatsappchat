<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateVapidKeysCommand extends Command
{
    protected $signature = 'webpush:generate-keys';

    protected $description = 'Generate VAPID keys for Web Push (add to .env as VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY)';

    public function handle(): int
    {
        if (class_exists(\Minishlink\WebPush\VAPID::class)) {
            $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
            $this->line('Add these to your .env file:');
            $this->newLine();
            $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
            $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
            $this->newLine();
            return 0;
        }

        $this->warn('Install minishlink/web-push to generate keys:');
        $this->line('  composer require minishlink/web-push');
        $this->line('  php artisan webpush:generate-keys');
        $this->newLine();
        $this->line('Or generate keys online (e.g. vapidkeys.com) and set VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY in .env.');
        return 0;
    }
}
