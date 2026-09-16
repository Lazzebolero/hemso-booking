@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
    $tourStatus = $tour->status ?? 'planned';
    $isOngoingTour = $tourStatus === 'started';
    $isArchiveTour = $tourStatus === 'completed'
        || ($tour->tour_date && $tour->tour_date->toDateString() < now()->toDateString());
    $toursIndexUrl = route($prefix . '.tours.index', $isArchiveTour ? ['scope' => 'archive'] : ['scope' => 'upcoming']);
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">{{ $tour->title }}</h2>
        <div class="page-subtitle">
            {{ $tour->tour_date ? \Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d') : '-' }}
            @if($isOngoingTour)
                <span class="text-muted">· Pågående tur — bokningar kan uppdateras här.</span>
            @elseif($isArchiveTour)
                <span class="text-muted">· Genomförd tur — bokningar kan rättas i efterhand.</span>
            @endif
        </div>
    </div>

    <div class="page-actions">
        <a href="{{ $toursIndexUrl }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>

        <a href="{{ route($prefix . '.bookings.create', ['tour_id' => $tour->id]) }}" class="btn btn-primary">
            <i class="bi bi-journal-plus me-2"></i>Boka
        </a>

        <a href="{{ route($prefix . '.tours.edit', $tour) }}" class="btn btn-outline-secondary">
            <i class="bi bi-pencil me-2"></i>Redigera
        </a>

        @if($tour->status === 'planned')
            @include('partials.admin.tour-start-form', [
                'tour' => $tour,
                'prefix' => $prefix,
                'buttonClass' => 'btn btn-outline-secondary',
                'buttonIconClass' => 'bi bi-play-fill me-2',
            ])
        @endif

        @include('partials.admin.tour-close-bookings-form', [
            'tour' => $tour,
            'prefix' => $prefix,
            'buttonClass' => 'btn btn-outline-warning',
            'reopenButtonClass' => 'btn btn-outline-secondary',
        ])

        @if($tour->status === 'started')
            <form method="POST" action="{{ route($prefix . '.tours.complete', $tour) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-stop-fill me-2"></i>Avsluta tur
                </button>
            </form>
        @endif

        @include('partials.admin.tour-delete-form', [
            'tour' => $tour,
            'bookingsCount' => $tour->bookings->count(),
            'buttonClass' => 'btn btn-outline-danger',
        ])
    </div>
</div>

@if($tour->closed_for_bookings)
    <div class="alert alert-warning">
        <strong>Stängd för bokning.</strong>
        Turen syns inte i bokningssekvensen och tar inte emot fler bokningar där.
    </div>
@endif

@if(!empty($guideLanguageMismatch['has_language_mismatch']))
    <div class="alert alert-warning">
        <strong>Språkmismatch:</strong>
        {{ $guideLanguageMismatch['message'] }}
        Turprogrammet kräver {{ implode(', ', $guideLanguageMismatch['required_language_codes']) }}.
        @if($tour->guide)
            {{ $tour->guide->name }} kan guida på {{ $tour->guide->guideLanguageLabel() }}.
        @endif
    </div>
@endif

@include('partials.admin.tour-bookings-table', [
    'tour' => $tour,
    'prefix' => $prefix,
])

@php
    $languageCodes = $tour->bookings
        ->flatMap(fn ($booking) => $booking->languages->pluck('code'))
        ->filter()
        ->map(fn ($code) => strtoupper($code))
        ->unique()
        ->values();

    $activeBookings = $tour->bookings
        ->whereNotIn('status', ['cancelled']);

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
                <div class="info-label">Mat (standard)</div>
                <div class="info-value">
                    @if($tour->default_includes_meal)
                        @include('partials.tours.meal-badge', ['tour' => $tour])
                    @else
                        <span class="badge-soft badge-soft-secondary">Ej mat</span>
                    @endif
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Turtyp</div>
                <div class="info-value">{{ $tour->tourType?->name ?? '-' }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Huvudguide</div>
                <div class="info-value">{{ $tour->guide?->name ?? 'Ej tilldelad' }}</div>
            </div>

            @if($tour->coGuides->isNotEmpty())
                <div class="info-item">
                    <div class="info-label">Medguider</div>
                    <div class="info-value">{{ app(\App\Services\TourCoGuideService::class)->coGuideSummary($tour) }}</div>
                </div>
            @endif

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

            @if($tour->started_at)
                <div class="info-item">
                    <div class="info-label">Verklig start</div>
                    <div class="info-value">
                        {{ method_exists($tour, 'formattedActualStartedAt') ? $tour->formattedActualStartedAt() : ($tour->started_at?->format('Y-m-d H:i') ?? '-') }}
                    </div>
                </div>
            @endif

            @if($tour->ended_at)
                <div class="info-item">
                    <div class="info-label">Verklig slut</div>
                    <div class="info-value">
                        {{ method_exists($tour, 'formattedActualEndedAt') ? $tour->formattedActualEndedAt() : ($tour->ended_at?->format('Y-m-d H:i') ?? '-') }}
                        @if(method_exists($tour, 'wasAutoCompleted') && $tour->wasAutoCompleted())
                            <span class="badge-soft badge-soft-secondary ms-1">Automatiskt avslutad</span>
                        @endif
                    </div>
                </div>
            @endif

            @if(method_exists($tour, 'actualDurationLabel') && $tour->actualDurationLabel())
                <div class="info-item">
                    <div class="info-label">Turen tog</div>
                    <div class="info-value">{{ $tour->actualDurationLabel() }}</div>
                </div>
            @endif
        </div>

        @if(!empty($tour->description))
            <div class="mt-3">
                <div class="info-label mb-1">Beskrivning</div>
                <div class="small-muted">{!! nl2br(e($tour->description)) !!}</div>
            </div>
        @endif
    </div>
</div>

<div class="page-card compact-card mb-4">
    <div class="info-label mb-2">Ändringshistorik</div>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="small-muted mb-1">Skapad</div>
            <div>
                {{ $tour->created_at?->format('Y-m-d H:i') ?? '–' }}
                · {{ ($createdByUser ?? null)?->name ?? 'Okänd användare' }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="small-muted mb-1">Senast ändrad</div>
            <div>
                {{ $tour->updated_at?->format('Y-m-d H:i') ?? '–' }}
                · {{ ($updatedByUser ?? null)?->name ?? 'Okänd användare' }}
            </div>
        </div>
    </div>
</div>

<div class="page-card mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div>
            <div class="section-title mb-1">Bilder från turen</div>
            <div class="small-muted">Bilder som guide har laddat upp för turen.</div>
        </div>

        <span class="badge bg-light text-dark border">{{ $tour->photos->count() }} bilder</span>
    </div>

    @if($tour->photos->isNotEmpty())
        <div class="row g-3">
            @foreach($tour->photos as $photo)
                <div class="col-6 col-md-3">
                    <a href="{{ $photo->url }}" target="_blank" rel="noopener" class="d-block">
                        <img src="{{ $photo->url }}" alt="{{ $photo->caption ?: $photo->original_name ?: 'Turbild' }}" class="img-fluid rounded border">
                    </a>
                    <div class="small-muted mt-1">
                        {{ $photo->caption ?: $photo->original_name ?: 'Turbild' }}
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bi bi-images"></i>
            </div>
            <div class="fw-semibold">Inga bilder ännu</div>
            <div class="small-muted">När turbilder laddas upp visas de här.</div>
        </div>
    @endif
</div>
@endsection