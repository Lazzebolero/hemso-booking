<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Avvikelse vid öppningskontroll</title>
</head>
<body style="font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; line-height: 1.5; color: #1e293b; margin: 0; padding: 24px;">
    <p style="margin: 0 0 12px;">Hej,</p>
    <p style="margin: 0 0 16px;">En avvikelse har registrerats vid dagens öppningskontroll.</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%; max-width: 640px; border-collapse: collapse; margin: 0 0 20px;">
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; width: 180px; vertical-align: top; color: #64748b;">Datum</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top;"><strong>{{ $deviation->openingCheck?->check_date?->format('Y-m-d') ?? '—' }}</strong></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top; color: #64748b;">Kontrollpunkt</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top;">{{ $deviation->checkpointLabel() }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top; color: #64748b;">Plats / nödutgång</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top;">{{ $deviation->location ?: '—' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top; color: #64748b;">Status</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top;">{{ $deviation->statusLabel() }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top; color: #64748b;">Rapporterad av</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top;">{{ $deviation->reporter?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top; color: #64748b;">Öppningsansvarig</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top;">{{ $deviation->openingCheck?->openedBy?->name ?? '—' }}</td>
        </tr>
    </table>

    <div style="margin: 0 0 16px;">
        <div style="color: #64748b; margin-bottom: 6px;">Beskrivning</div>
        <div style="padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; white-space: pre-wrap;">{{ $deviation->description }}</div>
    </div>

    <div style="margin: 0 0 16px;">
        <div style="color: #64748b; margin-bottom: 6px;">Omedelbar åtgärd</div>
        <div style="padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; white-space: pre-wrap;">{{ $deviation->immediate_action ?: '—' }}</div>
    </div>

    <div style="margin: 0 0 16px;">
        <div style="color: #64748b; margin-bottom: 6px;">Vem informerades</div>
        <div style="padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; white-space: pre-wrap;">{{ $deviation->informed_person ?: '—' }}</div>
    </div>

    <div style="margin: 0 0 20px;">
        <div style="color: #64748b; margin-bottom: 6px;">Beslut innan öppning</div>
        <div style="padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; white-space: pre-wrap;">{{ $deviation->decision_before_opening ?: '—' }}</div>
    </div>

    @if(!empty($showUrl))
        <p style="margin: 0 0 16px;">
            <a href="{{ $showUrl }}" style="display: inline-block; padding: 10px 16px; background: #0f172a; color: #fff; text-decoration: none; border-radius: 6px;">
                Öppna protokollet i systemet
            </a>
        </p>
    @endif

    <p style="font-size: 13px; color: #64748b; margin: 0;">Detta meddelande skickades automatiskt från {{ config('app.name') }}.</p>
</body>
</html>
