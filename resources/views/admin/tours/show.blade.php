@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">{{ $tour->title }}</h2>
        <div class="page-subtitle">
            {{ $tour->tour_date ? \Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d') : '-' }}
        </div>
    </div>

    <div class="page-actions">
        <a href="{{ route($prefix . '.tours.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>

        <a href="{{ route($prefix . '.bookings.create', ['tour_id' => $tour->id]) }}" class="btn btn-primary">
            <i class="bi bi-journal-plus me-2"></i>Boka
        </a>

        @if($tour->status !== 'completed')
            <a href="{{ route($prefix . '.tours.edit', $tour) }}" class="btn btn-outline-secondary">
                <i class="bi bi-pencil me-2"></i>Redigera
            </a>
        @endif

        @if($tour->status === 'planned')
            <form method="POST" action="{{ route($prefix . '.tours.start', $tour) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="bi bi-play-fill me-2"></i>Starta tur
                </button>
            </form>
        @endif

        @if($tour->status === 'started')
            <form method="POST" action="{{ route($prefix . '.tours.complete', $tour) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-stop-fill me-2"></i>Avsluta tur
                </button>
            </form>
        @endif
    </div>
</div>

@php
    $languageCodes = $tour->bookings
        ->flatMap(fn ($booking) => $booking->languages->pluck('code'))
        ->filter()
        ->map(fn ($code) => strtoupper($code))
        ->unique()
        ->values();

    $activeBookings = $tour->bookings
        ->whereNotIn('status', ['cancelled'])
        ->where('is_waitlist', false);

    $totalPeople = $activeBookings->sum('total_count');
    $totalBookings = $activeBookings->count();

    $occupancy = ($tour->max_participants ?? 0) > 0
        ? round(($totalPeople / $tour->max_participants) * 100)
        : 0;

    $availableSpots = max(0, ($tour->max_participants ?? 0) - $totalPeople);
@endphp

<div class="stats-grid mb-4">
    <div class="stats-card">
        <div class="stats-label">Bokade personer</div>
        <div class="stats-value">{{ $totalPeople }}</div>
        <div class="stats-subtext">Totalt bokade på turen</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Antal bokningar</div>
        <div class="stats-value">{{ $totalBookings }}</div>
        <div class="stats-subtext">Aktiva bokningar</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Lediga platser</div>
        <div class="stats-value">{{ $availableSpots }}</div>
        <div class="stats-subtext">Kvar av {{ $tour->max_participants }}</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Beläggning</div>
        <div class="stats-value">{{ $occupancy }}%</div>
        <div class="stats-subtext">Aktuell fyllnadsgrad</div>
    </div>
</div>

<div class="admin-grid-2 mb-4">
    <div class="page-card">
        <div class="section-title">Turinformation</div>

        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Turtyp</div>
                <div class="info-value">{{ $tour->tourType?->name ?? '-' }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Guide</div>
                <div class="info-value">{{ $tour->guide?->name ?? 'Ej tilldelad' }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Språk</div>
                <div class="info-value">
                    @if($languageCodes->isEmpty())
                        -
                    @else
                        {{ $languageCodes->implode(' + ') }}
                    @endif
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Datum</div>
                <div class="info-value">{{ $tour->tour_date ? \Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d') : '-' }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Starttid</div>
                <div class="info-value">{{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Sluttid</div>
                <div class="info-value">{{ !empty($tour->end_time) ? substr($tour->end_time, 0, 5) : '-' }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Status</div>
                <div class="info-value">{{ ucfirst($tour->status ?? '-') }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Max deltagare</div>
                <div class="info-value">{{ $tour->max_participants ?? '-' }}</div>
            </div>
        </div>

        @if(!empty($tour->description))
            <div class="mt-3">
                <div class="info-label mb-1">Beskrivning</div>
                <div class="small-muted">{!! nl2br(e($tour->description)) !!}</div>
            </div>
        @endif
    </div>
</div>
@endsection