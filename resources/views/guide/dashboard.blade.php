@extends('layouts.app')

@section('content')
<div class="guide-mobile-header mb-4">
    <div>
        <h2 class="mb-1">Mina turer</h2>
        <div class="muted">Endast aktiva och kommande turer visas här.</div>
    </div>
	<a href="{{ route('quick-tours.create') }}" class="btn btn-primary">
        <i class="bi bi-lightning-charge-fill me-2"></i>Starta snabbtur
    </a>
</div>

<div class="guide-mobile-list">
    @forelse($tours as $tour)
        @php
            $bookingCount = $tour->bookings->where('status', '!=', 'cancelled')->count();
            $bookedCount = $tour->bookings->where('status', '!=', 'cancelled')->sum('total_count');
            $available = max(0, $tour->max_participants - $bookedCount);
            $percent = $tour->max_participants > 0 ? min(100, round(($bookedCount / $tour->max_participants) * 100)) : 0;
            $barColor = $available <= 0 ? 'var(--brand-danger)' : ($available <= 5 ? 'var(--brand-warning)' : 'var(--brand-success)');
        @endphp

        <a href="{{ route('guide.tours.show', $tour) }}" class="guide-tour-card-link">
            <div class="guide-tour-card">
                <div class="guide-tour-top">
                    <div>
                        <div class="guide-tour-title">{{ $tour->title }}</div>
                        <div class="guide-tour-meta">
                            <div><i class="bi bi-calendar-event me-1"></i>{{ $tour->tour_date }}</div>
                            <div><i class="bi bi-clock me-1"></i>{{ $tour->start_time }}{{ $tour->end_time ? ' - ' . $tour->end_time : '' }}</div>
                        </div>
                    </div>
                    <x-status-chip :status="$tour->status" />
                </div><div class="guide-tour-meta">
    <div><i class="bi bi-calendar-event me-1"></i>{{ $tour->tour_date }}</div>
    <div><i class="bi bi-clock me-1"></i>{{ $tour->start_time }}{{ $tour->end_time ? ' - ' . $tour->end_time : '' }}</div>

    @php
        $languageCodes = $tour->bookings
            ->flatMap(fn ($booking) => $booking->languages->pluck('code'))
            ->filter()
            ->map(fn ($code) => strtoupper($code))
            ->unique()
            ->values();
    @endphp

    <div class="mt-1">
        @if($languageCodes->isEmpty())
            <span class="muted small">Språk: -</span>
        @elseif($languageCodes->count() === 1)
            <span class="badge-soft badge-soft-secondary">
                {{ $languageCodes->first() }}
            </span>
        @else
            <span class="badge-soft badge-soft-danger">
                {{ $languageCodes->implode(' + ') }}
            </span>
        @endif
    </div>
</div>

                <div class="guide-tour-stats">
                    <div class="guide-stat-box">
                        <div class="guide-stat-label">Bokningar</div>
                        <div class="guide-stat-value">{{ $bookingCount }}</div>
                    </div>
                    <div class="guide-stat-box">
                        <div class="guide-stat-label">Bokade</div>
                        <div class="guide-stat-value">{{ $bookedCount }}</div>
                    </div>
                    <div class="guide-stat-box">
                        <div class="guide-stat-label">Lediga</div>
                        <div class="guide-stat-value">{{ $available }}</div>
                    </div>
                    <div class="guide-stat-box">
                        <div class="guide-stat-label">Startad</div>
                        <div class="guide-stat-value guide-stat-small">{{ $tour->started_at ?: '-' }}</div>
                    </div>
                </div>

                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-2">
                        <span>{{ $bookedCount }}/{{ $tour->max_participants }}</span>
                        <span class="muted">{{ $percent }}%</span>
                    </div>
                    <div class="progress-modern">
                        <div style="width: {{ $percent }}%; background: {{ $barColor }};"></div>
                    </div>
                </div>

                <div class="guide-card-footer">
                    <span class="muted small">Tryck för att se bokade grupper</span>
                    <i class="bi bi-chevron-right"></i>
                </div>
            </div>
        </a>
    @empty
        <div class="page-card">
            <div class="muted">Du har inga aktiva eller kommande turer.</div>
        </div>
    @endforelse
</div>

<style>
.guide-tour-card-link {
    text-decoration: none;
    color: inherit;
}
.guide-mobile-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    flex-wrap: wrap;
}
.guide-mobile-list {
    display: grid;
    gap: 1rem;
}
.guide-tour-card {
    background: rgba(255,255,255,0.96);
    border-radius: 22px;
    box-shadow: var(--shadow-soft);
    border: 1px solid rgba(255,255,255,0.75);
    padding: 1rem;
}
.guide-tour-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.8rem;
    margin-bottom: 1rem;
}
.guide-tour-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 0.35rem;
}
.guide-tour-meta {
    display: grid;
    gap: 0.2rem;
    color: var(--text-soft);
    font-size: 0.92rem;
}
.guide-tour-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}
.guide-stat-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 0.8rem;
}
.guide-stat-label {
    font-size: 0.82rem;
    color: var(--text-soft);
    margin-bottom: 0.25rem;
}
.guide-stat-value {
    font-size: 1.1rem;
    font-weight: 700;
}
.guide-stat-small {
    font-size: 0.9rem;
    word-break: break-word;
}
.guide-card-footer {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:0.8rem;
}
@media (max-width: 576px) {
    .content-area {
        padding: 1rem 0.85rem 5.5rem;
    }
    .guide-tour-card {
        padding: 0.9rem;
        border-radius: 18px;
    }
    .guide-tour-top {
        flex-direction: column;
        align-items: stretch;
    }
    .guide-tour-stats {
        grid-template-columns: 1fr 1fr;
        gap: 0.6rem;
    }
}
</style>
@endsection
