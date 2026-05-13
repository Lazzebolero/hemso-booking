<?php

namespace App\Mail;

use App\Models\SystemMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SystemMessageAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public SystemMessage $systemMessage;

    public function __construct(SystemMessage $systemMessage)
    {
        $this->systemMessage = $systemMessage;
    }

    public function build()
    {
        return $this
            ->subject('Systemmeddelande: ' . $this->systemMessage->title)
            ->view('emails.system-message-alert')
            ->with([
                'systemMessage' => $this->systemMessage,
            ]);
    }
}