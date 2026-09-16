@props([
    'tour',
    'waitWarningMinutes' => 45,
])

@php
    $waitWarn = (bool) ($tour->wait_warn ?? false);
    $waitMax = $tour->wait_max_minutes ?? null;
    $waitAvg = $tour->wait_avg_minutes ?? null;
    $chosenSamples = (int) ($tour->wait_chosen_samples ?? 0);
    $chosenMax = $tour->wait_chosen_max_minutes ?? null;
    $waitComputed = (bool) ($tour->wait_computed ?? false);
    $hasBookings = (int) ($tour->booking_groups_count ?? 0) > 0
        || (int) ($tour->booked_people_count ?? 0) > 0;
    $hasSameDay = $waitMax !== null || $chosenSamples > 0;
@endphp

@if(! $hasSameDay)
    @if($waitComputed && $hasBookings)
        <span class="small-muted">Inga samma-dag</span>
    @else
        <span class="small-muted">–</span>
    @endif
@else
    @if($waitMax !== null)
        <div class="fw-semibold">Max {{ $waitMax }} m</div>
        <div class="small-muted">Snitt {{ number_format((float) $waitAvg, 0, ',', ' ') }} m</div>
        @if($waitWarn)
            <div class="fw-bold text-uppercase tour-wait-over-threshold">ÖVER {{ $waitWarningMinutes }} m</div>
        @endif
    @else
        <div class="small-muted">Ingen kö</div>
    @endif

    @if($chosenSamples > 0)
        <div class="small-muted mt-1">
            Förbokade {{ $chosenSamples }}
            @if($chosenMax !== null)
                · max {{ $chosenMax }} m
            @endif
        </div>
    @endif
@endif

@once
    <style>
        .tour-wait-over-threshold {
            color: #b91c1c;
            letter-spacing: 0.02em;
        }
    </style>
@endonce
