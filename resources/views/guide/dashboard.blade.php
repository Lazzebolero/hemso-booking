@extends('layouts.guide')

@section('content')
<div class="guide-dashboard">

    <div id="guide-offline-ongoing-hint" class="alert alert-info mb-3 d-none" role="status">
        Du har en pågående tur sparad lokalt.
        <a id="guide-offline-ongoing-link" href="#" class="alert-link ms-1">Öppna tur</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-3">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if(($openingCheckTablesReady ?? false) && \Illuminate\Support\Facades\Route::has('guide.opening-checks.edit'))
    <div class="{{ ($todayOpeningCheckCompleted ?? false) ? 'guide-card' : 'guide-card guide-card-opening-warning' }} mb-3">
        <div class="guide-card-label">Öppningskontroll</div>
        @if($todayOpeningCheckCompleted ?? false)
            <div class="guide-card-title">Dagens kontroll är klar</div>
            <div class="guide-muted mb-3">
                Genomförd av {{ $todayOpeningCheck?->openedBy?->name ?? 'guide' }}
                @if($todayOpeningCheck?->completed_at)
                    · {{ $todayOpeningCheck->completed_at->format('H:i') }}
                @endif
            </div>
            <a href="{{ route('guide.opening-checks.edit') }}" class="btn btn-outline-secondary">Öppna protokollet</a>
        @else
            <div class="guide-card-title">Dagens kontroll är inte slutförd</div>
            <div class="guide-muted mb-3">
                Anläggningen ska inte öppnas för besökare förrän öppningskontrollen är gjord. Turer kan ändå startas.
            </div>
            <a href="{{ route('guide.opening-checks.edit') }}" class="btn btn-primary">
                {{ ($todayOpeningCheck ?? null) ? 'Fortsätt kontrollen' : 'Starta öppningskontroll' }}
            </a>
        @endif
    </div>
    @endif

    <div class="guide-summary-grid mb-3">
        <div class="guide-card">
            <div class="guide-card-label">Kommande turer</div>
            <div class="guide-card-value">{{ $upcomingTourCount ?? 0 }}</div>
        </div>

        <div class="guide-card">
            <div class="guide-card-label">Bokade deltagare</div>
            <div class="guide-card-value">{{ $upcomingParticipantCount ?? 0 }}</div>
        </div>
    </div>

    @if(($coGuideTours ?? collect())->isNotEmpty())
        <div class="guide-card guide-card-co-guide mb-3">
            <div class="guide-card-label">Turer du följer med på</div>
            <div class="form-text mb-3">
                Du är tillagd som medguide. Huvudguiden startar och hanterar turen.
            </div>

            @foreach($coGuideTours as $coGuideTour)
                <div class="guide-co-guide-row">
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-1">
                            <div class="fw-bold">
                                {{ \Carbon\Carbon::parse($coGuideTour->tour_date)->translatedFormat('D j M') }}
                                · {{ substr($coGuideTour->start_time, 0, 5) }}
                                · {{ $coGuideTour->tourType->name ?? 'Tur' }}
                            </div>
                            @include('partials.tours.language-badge', ['tour' => $coGuideTour])
                            @include('partials.tours.meal-badge', ['tour' => $coGuideTour])
                        </div>
                        <div class="guide-muted mt-1">
                            Huvudguide: <strong>{{ $coGuideTour->guide?->name ?? 'Ej tilldelad' }}</strong>
                            · Din roll: <strong>{{ $coGuideTour->co_guide_role_label ?? 'Medguide' }}</strong>
                            @if(($coGuideTour->status ?? null) === 'started')
                                · <span class="text-primary">Pågående</span>
                            @endif
                        </div>
                        <div class="guide-muted mt-1">
                            {{ $coGuideTour->booked_people_count ?? 0 }} bokade deltagare
                        </div>
                        @if(!empty($coGuideTour->co_guide_notes))
                            <div class="guide-co-guide-note mt-2">
                                {{ $coGuideTour->co_guide_notes }}
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if($ongoingTour)
        <div class="guide-card guide-card-highlight mb-3">
            <div class="guide-card-label">Pågående tur</div>
            <h2 class="guide-card-title">
                {{ $ongoingTour->tourType->name ?? 'Tur' }}
            </h2>
            <div class="d-flex flex-wrap align-items-center gap-1">
                @include('partials.tours.language-badge', ['tour' => $ongoingTour])
                @include('partials.tours.meal-badge', ['tour' => $ongoingTour])
            </div>

            <div class="guide-muted">
                {{ \Carbon\Carbon::parse($ongoingTour->tour_date)->translatedFormat('D j M') }}
                · {{ substr($ongoingTour->start_time, 0, 5) }}
            </div>

            <div class="guide-meta mt-2">
                <span>{{ $ongoingTour->booked_people_count ?? 0 }} deltagare</span>
                @if(isset($ongoingTour->booking_groups_count))
                    <span>{{ $ongoingTour->booking_groups_count }} grupper</span>
                @endif
            </div>

            <div class="guide-actions mt-3">
                <a href="{{ route('guide.tours.show', $ongoingTour) }}" class="btn btn-primary">
                    <i class="bi bi-eye me-1"></i>Öppna tur
                </a>

                @if(Route::has('guide.tours.complete'))
                    <form method="POST" action="{{ route('guide.tours.complete', $ongoingTour) }}" class="d-inline" data-offline-queue>
                        @csrf
                        <button type="submit" class="btn btn-outline-success">
                            <i class="bi bi-check-circle me-1"></i>Avsluta tur
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    @if($nextTour)
        <div class="guide-card mb-3 {{ !empty($nextTour->is_due_to_start) ? 'guide-card-highlight' : '' }}">
            <div class="guide-card-label">
                {{ !empty($nextTour->is_due_to_start) ? 'Tur att starta' : 'Nästa tur' }}
            </div>
            <h2 class="guide-card-title">
                {{ $nextTour->tourType->name ?? 'Tur' }}
            </h2>
            <div class="d-flex flex-wrap align-items-center gap-1">
                @include('partials.tours.language-badge', ['tour' => $nextTour])
                @include('partials.tours.meal-badge', ['tour' => $nextTour])
            </div>

            <div class="guide-muted">
                {{ \Carbon\Carbon::parse($nextTour->tour_date)->translatedFormat('D j M') }}
                · {{ substr($nextTour->start_time, 0, 5) }}
            </div>

            @if(!empty($nextTour->is_due_to_start))
                <div class="guide-muted mt-2">
                    Planerad starttid har passerat — starta när gästerna är redo.
                </div>
            @endif

            <div class="guide-meta mt-2">
                <span>
                    Bokade deltagare:
                    <strong>{{ $nextTour->booked_people_count ?? 0 }}</strong>
                    @if(!empty($nextTour->category_summary))
                        <span class="guide-muted">({{ $nextTour->category_summary }})</span>
                    @endif
                </span>

                @if(isset($nextTour->booking_groups_count))
                    <span>{{ $nextTour->booking_groups_count }} grupper</span>
                @endif
            </div>

            <div class="guide-actions mt-3">
                <a href="{{ route('guide.tours.show', $nextTour) }}" class="btn btn-primary">
                    <i class="bi bi-eye me-1"></i>Öppna tur
                </a>

                @if(Route::has('guide.tours.start'))
                    <form method="POST" action="{{ route('guide.tours.start', $nextTour) }}" class="d-inline" data-offline-queue>
                        @csrf
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-play-circle me-1"></i>Starta tur
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @else
        <div class="guide-card mb-3">
            <div class="guide-card-label">Nästa tur</div>
            <div class="guide-muted">Ingen kommande tur hittades.</div>
        </div>
    @endif

    @if(Route::has('guide.memories.create'))
        <div class="guide-card mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <div class="guide-card-label">Anläggningsminnen</div>
                    <div class="guide-muted">
                        Spara berättelser från besökare — du ser bara dina egna insamlade minnen.
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if(Route::has('guide.memories.index'))
                        <a href="{{ route('guide.memories.index') }}" class="btn btn-outline-secondary">
                            Mina minnen
                        </a>
                    @endif
                    <a href="{{ route('guide.memories.create') }}" class="btn btn-outline-primary">
                        <i class="bi bi-journal-text me-1"></i>Spara minne
                    </a>
                </div>
            </div>
        </div>
    @endif

    <div class="guide-card">
        <div class="guide-section-header">
            <h2 class="guide-section-title">Kommande turer</h2>
        </div>

        @forelse($laterUpcomingTours as $tour)
            <div class="guide-tour-row">
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-1">
                        <div class="fw-bold">
                            {{ \Carbon\Carbon::parse($tour->tour_date)->translatedFormat('D j M') }}
                            · {{ substr($tour->start_time, 0, 5) }}
                            · {{ $tour->tourType->name ?? 'Tur' }}
                        </div>
                        @include('partials.tours.language-badge', ['tour' => $tour])
                        @include('partials.tours.meal-badge', ['tour' => $tour])
                    </div>
                    <div class="guide-muted">
                        {{ $tour->booked_people_count ?? 0 }} deltagare
                        @if(!empty($tour->category_summary))
                            · {{ $tour->category_summary }}
                        @endif
                    </div>
                </div>

                <a href="{{ route('guide.tours.show', $tour) }}" class="btn btn-sm btn-outline-primary">
                    Öppna
                </a>
            </div>
        @empty
            <div class="guide-muted">
                @if($nextTour)
                    Inga fler kommande turer efter nästa.
                @else
                    Inga kommande turer.
                @endif
            </div>
        @endforelse
    </div>
</div>

<style>
    .guide-dashboard {
        display: grid;
        gap: 1rem;
    }

    .guide-summary-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
    }

    .guide-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 1rem;
        box-shadow: 0 10px 28px rgba(15, 23, 42, .06);
    }

    .guide-card-highlight {
        border-color: #93c5fd;
        background: #eff6ff;
    }

    .guide-card-opening-warning {
        border-color: #facc15;
        background: #fefce8;
    }

    .guide-card-co-guide {
        border-color: #c4b5fd;
        background: #f5f3ff;
    }

    .guide-co-guide-row {
        padding: 0.75rem 0;
        border-top: 1px solid #e9d5ff;
    }

    .guide-co-guide-row:first-of-type {
        border-top: 0;
        padding-top: 0;
    }

    .guide-co-guide-note {
        display: inline-block;
        background: #ede9fe;
        color: #5b21b6;
        border-radius: 999px;
        padding: 0.3rem 0.65rem;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .guide-card-label {
        color: #64748b;
        font-size: .82rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .03em;
        margin-bottom: .35rem;
    }

    .guide-card-value {
        color: #0f172a;
        font-size: 1.8rem;
        font-weight: 900;
        line-height: 1;
    }

    .guide-card-title {
        color: #0f172a;
        font-size: 1.25rem;
        font-weight: 900;
        margin: 0 0 .35rem;
    }

    .guide-muted {
        color: #64748b;
        font-size: .9rem;
    }

    .guide-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        color: #0f172a;
        font-size: .9rem;
    }

    .guide-meta span {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        padding: .35rem .6rem;
    }

    .guide-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .guide-section-title {
        font-size: 1.05rem;
        font-weight: 900;
        margin: 0;
    }

    .guide-section-header {
        margin-bottom: .75rem;
    }

    .guide-tour-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
        padding: .75rem 0;
        border-top: 1px solid #e2e8f0;
    }

    .guide-tour-row:first-of-type {
        border-top: 0;
        padding-top: 0;
    }

    @media (max-width: 575.98px) {
        .guide-summary-grid {
            grid-template-columns: 1fr 1fr;
        }

        .guide-tour-row {
            align-items: flex-start;
        }
    }
</style>

@if(session('warm_guide_tour_id'))
    <meta name="guide-warm-tour-id" content="{{ session('warm_guide_tour_id') }}">
@endif

@if($ongoingTour)
    <meta name="guide-offline-ongoing-tour-url" content="{{ route('guide.tours.show', $ongoingTour) }}">
@endif

@php
    $offlineTourWarmUrls = collect([$ongoingTour ?? null, $nextTour ?? null])
        ->merge($laterUpcomingTours ?? collect())
        ->filter()
        ->map(fn ($tour) => route('guide.tours.show', $tour))
        ->unique()
        ->values();
@endphp
@if($offlineTourWarmUrls->isNotEmpty())
    <meta name="guide-offline-tour-urls" content="{{ $offlineTourWarmUrls->toJson() }}">
@endif

@php
    $guideLiveSyncPath = public_path('js/guide-live-sync.js');
    $guideLiveSyncVer = is_file($guideLiveSyncPath) ? (string) filemtime($guideLiveSyncPath) : '0';
@endphp
<meta name="guide-live-sync" content="dashboard">
<script src="{{ asset('js/guide-live-sync.js') }}?v={{ $guideLiveSyncVer }}" defer></script>
<script>
    (function () {
        try {
            var ongoingUrl = sessionStorage.getItem('hemso-guide-ongoing-tour-url');
            if (!ongoingUrl) {
                return;
            }

            if (document.querySelector('.guide-card-highlight')) {
                return;
            }

            var hint = document.getElementById('guide-offline-ongoing-hint');
            var link = document.getElementById('guide-offline-ongoing-link');

            if (hint && link) {
                link.href = ongoingUrl;
                hint.classList.remove('d-none');
            }
        } catch (e) {
            // ignore
        }
    })();
</script>
@endsection
