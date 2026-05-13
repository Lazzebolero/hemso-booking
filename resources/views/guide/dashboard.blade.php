@extends('layouts.guide')

@section('content')
<div class="guide-dashboard">

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

    @if($ongoingTour)
        <div class="guide-card guide-card-highlight mb-3">
            <div class="guide-card-label">Pågående tur</div>
            <h2 class="guide-card-title">
                {{ $ongoingTour->tourType->name ?? 'Tur' }}
            </h2>

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
        <div class="guide-card mb-3">
            <div class="guide-card-label">Nästa tur</div>
            <h2 class="guide-card-title">
                {{ $nextTour->tourType->name ?? 'Tur' }}
            </h2>

            <div class="guide-muted">
                {{ \Carbon\Carbon::parse($nextTour->tour_date)->translatedFormat('D j M') }}
                · {{ substr($nextTour->start_time, 0, 5) }}
            </div>

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

    <div class="guide-card mb-3">
        <div class="guide-section-header">
            <h2 class="guide-section-title">Dagens turer</h2>
        </div>

        @forelse($todayTours as $tour)
            <div class="guide-tour-row">
                <div>
                    <div class="fw-bold">{{ substr($tour->start_time, 0, 5) }} · {{ $tour->tourType->name ?? 'Tur' }}</div>
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
            <div class="guide-muted">Inga turer idag.</div>
        @endforelse
    </div>

    <div class="guide-card">
        <div class="guide-section-header">
            <h2 class="guide-section-title">Kommande turer</h2>
        </div>

        @forelse($upcomingTours as $tour)
            <div class="guide-tour-row">
                <div>
                    <div class="fw-bold">
                        {{ \Carbon\Carbon::parse($tour->tour_date)->translatedFormat('D j M') }}
                        · {{ substr($tour->start_time, 0, 5) }}
                        · {{ $tour->tourType->name ?? 'Tur' }}
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
            <div class="guide-muted">Inga kommande turer.</div>
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
@endsection
