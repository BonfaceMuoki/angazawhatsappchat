<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailCommand extends Command
{
    protected $signature = 'mail:test {to=admin@example.com}';

    protected $description = 'Send a test email (use to verify Mailtrap/SMTP from Docker)';

    public function handle(): int
    {
        $to = $this->argument('to');
        $this->info('Sending test email to ' . $to . ' via ' . config('mail.default') . '...');

        try {
            Mail::raw('Test email from Angaza at ' . now()->toDateTimeString(), function ($message) use ($to) {
                $message->to($to)->subject('Angaza mail test');
            });
            $this->info('Sent. Check your Mailtrap inbox (or mail log).');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
