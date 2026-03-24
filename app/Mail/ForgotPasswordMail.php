<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForgotPasswordMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public string $resetUrl;
    public string $appName;

    public function __construct(User $user, string $resetUrl)
    {
        $this->user = $user;
        $this->resetUrl = $resetUrl;
        $this->appName = config('app.name', 'BoroBazar');
    }

    public function build(): self
    {
        return $this
            ->subject("Reset your password - {$this->appName}")
            ->view('emails.forgot-password')
            ->with([
                'resetUrl' => $this->resetUrl,
                'appName' => $this->appName,
            ]);
    }
}

