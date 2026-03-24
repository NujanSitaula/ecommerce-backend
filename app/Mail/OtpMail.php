<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public string $code;
    public string $appName;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $code)
    {
        $this->user = $user;
        $this->code = $code;
        $this->appName = config('app.name', 'BoroBazar');
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this
            ->subject("Your {$this->appName} verification code")
            ->view('emails.otp');
    }
}


