<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationWelcomeMail extends Mailable implements ShouldQueue
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
            ->subject("Welcome to {$this->appName}")
            ->view('emails.registration-welcome')
            ->with([
                'user' => $this->user,
                'appName' => $this->appName,
            ]);
    }
}

