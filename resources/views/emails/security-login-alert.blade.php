<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Säkerhetsvarning</title>
</head>
<body style="margin:0; padding:0; background:#f8fafc; font-family:Arial, sans-serif; color:#0f172a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; padding:24px 0;">
        <tr>
            <td align="center">
                <table width="640" cellpadding="0" cellspacing="0" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; overflow:hidden;">
                    <tr>
                        <td style="padding:22px 26px; background:#991b1b; color:#ffffff;">
                            <h1 style="margin:0; font-size:22px;">Säkerhetsvarning</h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:26px;">
                            <h2 style="margin:0 0 12px; font-size:20px;">
                                Många misslyckade inloggningar
                            </h2>

                            <p style="font-size:15px; line-height:1.6;">
                                Systemet har upptäckt ovanligt många misslyckade inloggningsförsök.
                            </p>

                            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; margin-top:16px;">
                                <tr>
                                    <td style="border-bottom:1px solid #e2e8f0; color:#64748b;">Period</td>
                                    <td style="border-bottom:1px solid #e2e8f0; font-weight:bold;">
                                        Senaste {{ $alertData['minutes'] }} minuterna
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border-bottom:1px solid #e2e8f0; color:#64748b;">Misslyckade försök</td>
                                    <td style="border-bottom:1px solid #e2e8f0; font-weight:bold;">
                                        {{ $alertData['failed_count'] }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border-bottom:1px solid #e2e8f0; color:#64748b;">Unika IP-adresser</td>
                                    <td style="border-bottom:1px solid #e2e8f0; font-weight:bold;">
                                        {{ $alertData['unique_ips'] }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border-bottom:1px solid #e2e8f0; color:#64748b;">Tidpunkt</td>
                                    <td style="border-bottom:1px solid #e2e8f0; font-weight:bold;">
                                        {{ $alertData['checked_at'] }}
                                    </td>
                                </tr>
                            </table>

                            @if(!empty($alertData['top_ips']))
                                <h3 style="margin:22px 0 8px; font-size:16px;">IP-adresser med flest försök</h3>

                                <ul style="margin-top:8px;">
                                    @foreach($alertData['top_ips'] as $ip)
                                        <li>{{ $ip['ip_address'] }} – {{ $ip['attempts'] }} försök</li>
                                    @endforeach
                                </ul>
                            @endif

                            @if(!empty($alertData['top_emails']))
                                <h3 style="margin:22px 0 8px; font-size:16px;">E-postadresser med flest försök</h3>

                                <ul style="margin-top:8px;">
                                    @foreach($alertData['top_emails'] as $email)
                                        <li>{{ $email['email'] }} – {{ $email['attempts'] }} försök</li>
                                    @endforeach
                                </ul>
                            @endif

                            <div style="margin-top:24px; padding:14px 16px; background:#fef2f2; border-radius:10px; color:#991b1b; font-size:13px;">
                                Kontrollera säkerhetsöversikten och inloggningsloggen i admin.
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