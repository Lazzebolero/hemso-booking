<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>{{ $messageModel->title }}</title>
</head>
<body style="margin:0; padding:24px; background:#f8fafc; font-family:Arial, Helvetica, sans-serif; color:#0f172a;">
    <div style="max-width:700px; margin:0 auto; background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; padding:24px;">
        <div style="font-size:12px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom:12px;">
            {{ $messageModel->message_type === 'alert' ? 'Driftlarm' : 'Systemmeddelande' }}
        </div>

        <h1 style="margin:0 0 14px; font-size:24px; line-height:1.2;">
            {{ $messageModel->title }}
        </h1>

        @if(!empty($messageModel->body))
            <div style="font-size:15px; line-height:1.65; color:#334155; white-space:pre-line;">
                {{ $messageModel->body }}
            </div>
        @endif

        <div style="margin-top:24px; padding-top:16px; border-top:1px solid #e2e8f0; font-size:13px; color:#64748b;">
            Prioritet: {{ $messageModel->priority_label }}
            @if($messageModel->requires_ack)
                • Kräver kvittering i systemet
            @endif
        </div>
    </div>
</body>
</html>