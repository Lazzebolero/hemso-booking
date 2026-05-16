<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Daglig systemstatus</title>
</head>
<body style="margin:0; padding:0; background:#f8fafc; font-family:Arial, sans-serif; color:#0f172a;">
    @php
        $statusColor = match ($overallStatus) {
            'ok' => '#166534',
            'warning' => '#92400e',
            'error' => '#991b1b',
            default => '#334155',
        };

        $statusBackground = match ($overallStatus) {
            'ok' => '#dcfce7',
            'warning' => '#fef3c7',
            'error' => '#fee2e2',
            default => '#e2e8f0',
        };
    @endphp

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; padding:24px 0;">
        <tr>
            <td align="center">
                <table width="680" cellpadding="0" cellspacing="0" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; overflow:hidden;">
                    <tr>
                        <td style="padding:22px 26px; background:#0f172a; color:#ffffff;">
                            <h1 style="margin:0; font-size:22px;">Daglig systemstatus</h1>
                            <div style="margin-top:6px; color:#cbd5e1; font-size:13px;">
                                {{ $appName }} · {{ $environment }} · {{ $checkedAt }}
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:26px;">
                            <div style="padding:16px 18px; background:{{ $statusBackground }}; color:{{ $statusColor }}; border-radius:12px;">
                                <div style="font-size:13px; text-transform:uppercase; letter-spacing:.04em;">Övergripande status</div>
                                <div style="font-size:26px; font-weight:bold; margin-top:4px;">{{ $overallStatusLabel }}</div>
                            </div>

                            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; margin-top:18px;">
                                <tr>
                                    <td style="border-bottom:1px solid #e2e8f0; color:#64748b;">OK</td>
                                    <td style="border-bottom:1px solid #e2e8f0; font-weight:bold;">{{ $summary['ok'] }}</td>
                                </tr>
                                <tr>
                                    <td style="border-bottom:1px solid #e2e8f0; color:#64748b;">Varningar</td>
                                    <td style="border-bottom:1px solid #e2e8f0; font-weight:bold;">{{ $summary['warning'] }}</td>
                                </tr>
                                <tr>
                                    <td style="border-bottom:1px solid #e2e8f0; color:#64748b;">Fel</td>
                                    <td style="border-bottom:1px solid #e2e8f0; font-weight:bold;">{{ $summary['error'] }}</td>
                                </tr>
                            </table>

                            <h2 style="margin:24px 0 12px; font-size:18px;">Kontroller</h2>

                            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;">
                                @foreach($checks as $check)
                                    @php
                                        $checkColor = match ($check['status']) {
                                            'ok' => '#166534',
                                            'warning' => '#92400e',
                                            'error' => '#991b1b',
                                            default => '#334155',
                                        };
                                    @endphp

                                    <tr>
                                        <td style="border-top:1px solid #e2e8f0; font-weight:bold; width:34%;">
                                            {{ $check['title'] }}
                                        </td>
                                        <td style="border-top:1px solid #e2e8f0; color:{{ $checkColor }}; font-weight:bold; width:18%;">
                                            {{ strtoupper($check['status']) }}
                                        </td>
                                        <td style="border-top:1px solid #e2e8f0; color:#475569;">
                                            {{ $check['message'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            <div style="margin-top:24px;">
                                <a href="{{ $dashboardUrl }}" style="display:inline-block; padding:11px 16px; background:#2563eb; color:#ffffff; text-decoration:none; border-radius:8px; font-weight:bold;">
                                    Öppna systemhälsa
                                </a>
                            </div>

                            <div style="margin-top:20px; padding:14px 16px; background:#f1f5f9; border-radius:10px; color:#475569; font-size:13px;">
                                Detta mail skickas av Laravel scheduler. Andra tider eller mottagare styrs via serverns <code>.env</code>.
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
