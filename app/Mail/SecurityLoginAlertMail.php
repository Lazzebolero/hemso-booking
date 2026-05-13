<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SecurityLoginAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $alertData;

    public function __construct(array $alertData)
    {
        $this->alertData = $alertData;
    }

    public function build()
    {
        return $this
            ->subject('Säkerhetsvarning: många misslyckade inloggningar')
            ->view('emails.security-login-alert')
            ->with([
                'alertData' => $this->alertData,
            ]);
    }
}