<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>{{ $systemMessage->title }}</title>
</head>
<body style="margin:0; padding:0; background:#f8fafc; font-family:Arial, sans-serif; color:#0f172a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; padding:24px 0;">
        <tr>
            <td align="center">
                <table width="640" cellpadding="0" cellspacing="0" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; overflow:hidden;">
                    <tr>
                        <td style="padding:22px 26px; background:#0f172a; color:#ffffff;">
                            <h1 style="margin:0; font-size:22px;">Systemmeddelande</h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:26px;">
                            <h2 style="margin:0 0 12px; font-size:20px; color:#0f172a;">
                                {{ $systemMessage->title }}
                            </h2>

                            @if($systemMessage->body)
                                <div style="font-size:15px; line-height:1.6; color:#334155;">
                                    {!! nl2br(e($systemMessage->body)) !!}
                                </div>
                            @endif

                            <div style="margin-top:24px; padding:14px 16px; background:#f1f5f9; border-radius:10px; color:#475569; font-size:13px;">
                                Detta är ett automatiskt utskick från bokningssystemet.
                            </div>
                        </td>
                    </tr>
                </table>

                <div style="margin-top:14px; color:#94a3b8; font-size:12px;">
                    Hemsö Fästning
                </div>
            </td>
        </tr>
    </table>
</body>
</html>