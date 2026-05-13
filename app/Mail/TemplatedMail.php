<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemplatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $subjectLine;
    public string $htmlBody;

    public function __construct(string $subjectLine, string $htmlBody)
    {
        $this->subjectLine = $subjectLine;
        $this->htmlBody = $htmlBody;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml()
        );
    }

    protected function buildHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$this->escape($this->subjectLine)}</title>
</head>
<body style="margin:0; padding:0; background:#f8fafc; font-family:Arial, Helvetica, sans-serif; color:#0f172a;">
    <div style="max-width:680px; margin:0 auto; padding:32px 16px;">
        <div style="background:#ffffff; border-radius:18px; padding:32px; box-shadow:0 10px 30px rgba(15,23,42,0.08); border:1px solid #e2e8f0;">
            <div style="text-align:center; margin-bottom:24px;">
                <div style="font-size:24px; font-weight:700; color:#0f172a;">Hemsö</div>
            </div>

            <div style="font-size:15px; line-height:1.7; color:#1e293b;">
                {$this->htmlBody}
            </div>
        </div>

        <div style="text-align:center; font-size:12px; color:#64748b; margin-top:16px;">
            Detta e-postmeddelande skickades från Hemsö bokningssystem.
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public function attachments(): array
    {
        return [];
    }
}