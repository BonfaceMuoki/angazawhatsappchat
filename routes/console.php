<?php

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('whatsapp:reset-session {phone : The user phone number (e.g. 254740857767)} {--clear-messages : Also delete all messages for this phone}', function () {
    $phone = $this->argument('phone');
    $clearMessages = $this->option('clear-messages');

    $conversation = Conversation::where('phone', $phone)->first();
    if ($conversation) {
        $conversation->update([
            'flow_id' => null,
            'current_node_id' => null,
            'stage' => 'entry',
        ]);
        if ($clearMessages) {
            Message::where('phone', $phone)->delete();
        }
    }

    $this->info("Session reset for {$phone}. Flow/node cleared; next message will start from router/entry." . ($clearMessages ? ' Messages cleared.' : ''));
})->purpose('Reset a WhatsApp user conversation to the start of the flow');
