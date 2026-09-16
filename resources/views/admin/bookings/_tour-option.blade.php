@php
    $statusLabel = match ($tour->status ?? null) {
        'completed' => 'avslutad',
        'started' => 'pågår',
        'planned' => 'planerad',
        default => $tour->status,
    };
    $isSelected = $selectedTour === (string) $tour->id;
    $statusSuffix = ($showStatus ?? false) ? ' ('.$statusLabel.')' : '';
    $compact = $compact ?? false;
    $showOccupancy = $showOccupancy ?? true;

    $booked = isset($tour->booked_people_count)
        ? (int) $tour->booked_people_count
        : (int) collect($tour->bookings ?? [])
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_count');

    $max = (int) ($tour->max_participants ?? 0);
    $available = max(0, $max - $booked);
    $occupancySuffix = $showOccupancy ? ' · '.$booked.'/'.$max : '';

    $availabilityWarning = match (true) {
        $available === 0 => 'FULLBOKAD – ÖVERBOKNING MÖJLIG',
        $available === 1 => 'VARNING: ENDAST 1 PLATS KVAR',
        $available <= 4 => 'VARNING: ENDAST '.$available.' PLATSER KVAR',
        default => '',
    };

    $availabilitySuffix = $availabilityWarning !== '' ? ' · ⚠ '.$availabilityWarning : '';

    if ($compact) {
        $label = (! empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '—')
            .' – '.$tour->title
            .$occupancySuffix
            .$availabilitySuffix;
    } else {
        $label = ($tour->tour_date?->format('Y-m-d') ?? '—')
            .' '
            .(! empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '')
            .' – '.$tour->title
            .$statusSuffix
            .$occupancySuffix
            .$availabilitySuffix;
    }
@endphp

<option
    value="{{ $tour->id }}"
    {{ $isSelected ? 'selected' : '' }}
    data-default-meal="{{ $tour->default_includes_meal ? '1' : '0' }}"
    data-available="{{ $available }}"
    data-booked="{{ $booked }}"
    data-max="{{ $max }}"
    @if($availabilityWarning !== '') data-availability-warning="{{ $availabilityWarning }}" @endif
    @if($available === 0) data-overbook-allowed="1" @endif
>{{ $label }}</option>
