<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bokning att fakturera</title>
</head>
<body style="font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; line-height: 1.5; color: #1e293b;">
    <p>Hej,</p>
    <p>En ny bokning har markerats som <strong>faktureras</strong>.</p>

  @php
      $tour = $booking->tour;
  @endphp

    <p>
        <strong>Bokningsnamn:</strong> {{ $booking->booking_name }}<br>
        <strong>Kontakt:</strong> {{ $booking->contact_name ?: '—' }}<br>
        <strong>Telefon:</strong> {{ $booking->phone ?: '—' }}<br>
        <strong>E-post:</strong> {{ $booking->email ?: '—' }}<br>
        <strong>Antal:</strong> {{ $booking->total_count }}<br>
        <strong>Mat:</strong> {{ $booking->mealLabel() }}<br>
        @if($tour)
            <strong>Tur:</strong> {{ $tour->title }}<br>
            <strong>Datum:</strong> {{ $tour->tour_date?->format('Y-m-d') ?? '—' }}<br>
            <strong>Tid:</strong> {{ $tour->start_time ? substr((string) $tour->start_time, 0, 5) : '—' }}<br>
        @endif
        @if($booking->languages->isNotEmpty())
            <strong>Språk:</strong> {{ $booking->languages->pluck('name')->implode(', ') }}<br>
        @endif
        @if($booking->notes)
            <strong>Anteckning:</strong> {{ $booking->notes }}<br>
        @endif
    </p>

    <p>
        <a href="{{ $editUrl }}" style="display: inline-block; padding: 10px 16px; background: #0f172a; color: #fff; text-decoration: none; border-radius: 6px;">
            Öppna bokningen
        </a>
    </p>

    <p style="font-size: 13px; color: #64748b;">Detta meddelande skickades automatiskt från {{ config('app.name') }}.</p>
</body>
</html>
