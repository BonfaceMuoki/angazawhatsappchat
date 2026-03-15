<?php

namespace App\Jobs;

use App\Mail\PasswordResetEmail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPasswordResetEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public User $user,
        public string $token
    ) {}

    public function handle(): void
    {
        Mail::to($this->user->email)->send(new PasswordResetEmail($this->user, $this->token));
    }
}
