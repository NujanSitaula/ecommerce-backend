<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordSuccessMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public string $appName;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->appName = config('app.name', 'BoroBazar');
    }

    public function build(): self
    {
        return $this
            ->subject("Your password has been updated")
            ->view('emails.reset-password-success')
            ->with([
                'appName' => $this->appName,
            ]);
    }
}

