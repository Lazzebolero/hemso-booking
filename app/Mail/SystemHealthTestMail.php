<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SystemHealthTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $sentAt,
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Testmail från '.config('app.name').' – systemhälsa')
            ->view('emails.system-health-test')
            ->with([
                'user' => $this->user,
                'sentAt' => $this->sentAt,
                'appName' => config('app.name'),
                'environment' => app()->environment(),
            ]);
    }
}
