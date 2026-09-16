<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <title>Daglig systemstatus</title>
</head>
<body style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.5;">
@php
    $overallLabel = match ($report['overall_status']) {
        'ok' => 'OK',
        'warning' => 'Varning',
        'error' => 'Fel',
        default => 'Okänd',
    };

    $statusLabels = [
        'ok' => 'OK',
        'warning' => 'Varning',
        'error' => 'Fel',
    ];
@endphp

<h1 style="font-size: 20px; margin-bottom: 0;">Daglig systemstatus</h1>
<p style="margin-top: 6px; color: #64748b;">
    {{ config('app.name') }} · {{ app()->environment() }} · {{ $report['generated_at']->format('Y-m-d H:i:s') }}
</p>

<h2 style="font-size: 16px; margin-top: 24px;">ÖVERGRIPANDE STATUS</h2>
<p style="font-size: 18px; font-weight: bold;">{{ $overallLabel }}</p>
<p>
    OK {{ $report['summary']['ok'] ?? 0 }}<br>
    Varningar {{ $report['summary']['warning'] ?? 0 }}<br>
    Fel {{ $report['summary']['error'] ?? 0 }}
</p>

<h2 style="font-size: 16px; margin-top: 24px;">Kontroller</h2>
<table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%; max-width: 720px;">
    <thead>
        <tr style="background: #f8fafc;">
            <th align="left">Kontroll</th>
            <th align="left">Status</th>
            <th align="left">Meddelande</th>
        </tr>
    </thead>
    <tbody>
        @foreach($report['checks'] as $check)
            <tr>
                <td>{{ $check['title'] }}</td>
                <td>{{ $statusLabels[$check['status']] ?? strtoupper($check['status']) }}</td>
                <td>{{ $check['message'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<p style="margin-top: 24px;">
    <a href="{{ $systemHealthUrl }}">Öppna systemhälsa</a>
</p>

<p style="color: #64748b; font-size: 13px;">
    Detta mail skickas av Laravel scheduler. Andra tider eller mottagare styrs via serverns .env.
</p>
</body>
</html>
