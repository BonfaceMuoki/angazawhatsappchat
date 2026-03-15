<?php

namespace App\Jobs;

use App\Mail\OTPEmail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOTPEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public User $user,
        public string $code
    ) {}

    public function handle(): void
    {
        if (config('app.debug')) {
            \Illuminate\Support\Facades\Log::info('OTP for ' . $this->user->email . ': ' . $this->code);
        }
        Mail::to($this->user->email)->send(new OTPEmail($this->user, $this->code));
    }
}
