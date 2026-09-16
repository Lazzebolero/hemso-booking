<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ny felrapport</title>
</head>
<body style="font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; line-height: 1.5; color: #1e293b; margin: 0; padding: 24px;">
    <p style="margin: 0 0 12px;">Hej,</p>
    <p style="margin: 0 0 16px;">En ny felrapport har skickats in. All information finns i detta mejl@if(($attachmentCount ?? 0) > 0) och eventuella bilder är bifogade@endif.</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%; max-width: 640px; border-collapse: collapse; margin: 0 0 20px;">
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; width: 160px; vertical-align: top; color: #64748b;">Ärendenummer</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top;"><strong>#{{ $report->id }}</strong></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top; color: #64748b;">Rubrik</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top;"><strong>{{ $report->title }}</strong></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top; color: #64748b;">Skapad</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top;">{{ $report->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top; color: #64748b;">Status</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top;">{{ $report->statusRelation?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top; color: #64748b;">Kategori</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top;">{{ $report->category?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top; color: #64748b;">Klassning</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top;">{{ $report->priority?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top; color: #64748b;">Plats</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top;">
                @php
                    $locationParts = collect([
                        $report->location?->name,
                        $report->location_text,
                    ])->filter()->values();
                @endphp
                {{ $locationParts->isNotEmpty() ? $locationParts->implode(' — ') : '—' }}
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top; color: #64748b;">Inskickad av</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top;">
                {{ $report->reporter?->name ?? '—' }}
                @if(filled($report->reporter?->phone))
                    <br>Telefon: {{ $report->reporter->phone }}
                @endif
                @if(filled($report->reporter?->email))
                    <br>E-post: {{ $report->reporter->email }}
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top; color: #64748b;">Bilder</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top;">
                @if(($attachmentCount ?? 0) > 0)
                    {{ $attachmentCount }} st bifogade till detta mejl
                @else
                    Inga bilder bifogade
                @endif
            </td>
        </tr>
    </table>

    <div style="margin: 0 0 20px;">
        <div style="color: #64748b; margin-bottom: 6px;">Beskrivning</div>
        <div style="padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; white-space: pre-wrap;">{{ filled($report->description) ? $report->description : '—' }}</div>
    </div>

    @if(!empty($showUrl))
        <p style="margin: 0 0 16px;">
            <a href="{{ $showUrl }}" style="display: inline-block; padding: 10px 16px; background: #0f172a; color: #fff; text-decoration: none; border-radius: 6px;">
                Öppna felrapporten i systemet
            </a>
        </p>
        <p style="font-size: 13px; color: #64748b; margin: 0 0 16px;">
            Länken kräver inloggning och är främst för intern uppföljning. Extern entreprenör behöver inte öppna länken — all information finns ovan.
        </p>
    @endif

    <p style="font-size: 13px; color: #64748b; margin: 0;">Detta meddelande skickades automatiskt från {{ config('app.name') }}.</p>
</body>
</html>
