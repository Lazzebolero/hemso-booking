<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ny felrapport</title>
</head>
<body style="font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; line-height: 1.5; color: #1e293b;">
    <p>Hej,</p>
    <p>En ny felrapport har skickats in.</p>
    <p>
        <strong>Rubrik:</strong> {{ $report->title }}<br>
        <strong>Inskickad av:</strong> {{ $report->reporter?->name ?? '—' }}<br>
        @if($report->category)
            <strong>Kategori:</strong> {{ $report->category->name }}<br>
        @endif
        @if($report->priority)
            <strong>Klassning:</strong> {{ $report->priority->name }}<br>
        @endif
    </p>
    <p>
        <a href="{{ $showUrl }}" style="display: inline-block; padding: 10px 16px; background: #0f172a; color: #fff; text-decoration: none; border-radius: 6px;">
            Öppna felrapporten
        </a>
    </p>
    <p style="font-size: 13px; color: #64748b;">Detta meddelande skickades automatiskt från {{ config('app.name') }}.</p>
</body>
</html>
