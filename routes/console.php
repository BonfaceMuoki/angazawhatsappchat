<?php

use App\Services\WhatsAppBotService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('whatsapp:reset-session {phone : The user phone number (e.g. 254740857767)} {--clear-messages : Also delete all messages for this phone}', function () {
    $phone = $this->argument('phone');
    $clearMessages = $this->option('clear-messages');

    app(WhatsAppBotService::class)->resetConversation($phone, $clearMessages);

    $this->info("Session reset for {$phone}. Stage set to 'entry'." . ($clearMessages ? ' Messages cleared.' : ''));
})->purpose('Reset a WhatsApp user conversation to the start of the flow');
