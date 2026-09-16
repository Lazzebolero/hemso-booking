@extends('layouts.app')

@section('content')

<div class="page-card mb-3 staff-tours-card">
    <div class="staff-tours-head">
        <div class="section-title mb-0">Pågående turer</div>
        <div class="small-muted">Uppdateras var 30:e sek.</div>
    </div>

    @forelse($ongoingTours as $tour)
        @php
            $startedLabel = ! empty($tour->started_at)
                ? \Carbon\Carbon::parse($tour->started_at)->format('H:i')
                : (! empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-');

            $remainingLabel = match ($tour->remaining_to_end ?? '-') {
                '-' => null,
                'slutar nu' => 'Klar nu',
                'borde vara klar' => 'Borde vara klar',
                default => str_ends_with((string) $tour->remaining_to_end, ' kvar')
                    ? 'Klar om '.str_replace(' kvar', '', (string) $tour->remaining_to_end)
                    : (string) $tour->remaining_to_end,
            };
        @endphp

        <div class="staff-ongoing-row">
            <div class="staff-ongoing-main">
                <span class="staff-ongoing-title">{{ $tour->title }}</span>
                <span class="staff-ongoing-count">{{ $tour->booked_people_count }} pers</span>
            </div>
            <div class="staff-ongoing-meta">
                <span>Start {{ $startedLabel }}</span>
                <span class="staff-ongoing-sep">·</span>
                <span>Klar {{ $tour->estimated_end_time }}</span>
                @if($remainingLabel)
                    <span class="staff-ongoing-sep">·</span>
                    <span class="staff-ongoing-remaining">{{ $remainingLabel }}</span>
                @endif
            </div>
        </div>
    @empty
        <div class="small-muted staff-tours-empty">Inga pågående turer just nu.</div>
    @endforelse
</div>

<div class="page-card staff-tours-card">
    <div class="section-title mb-2">Dagens kommande turer</div>

    @forelse($upcomingToursToday ?? collect() as $tour)
        <div class="staff-upcoming-row">
            <span class="staff-upcoming-time">{{ ! empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}</span>
            <span class="staff-upcoming-title">{{ $tour->title }}</span>
            <span class="staff-upcoming-count">{{ $tour->booked_people_count }}</span>
        </div>
    @empty
        <div class="small-muted staff-tours-empty">Inga kommande turer idag.</div>
    @endforelse
</div>

<style>
.staff-tours-card {
    padding: 0.75rem 0.85rem;
}

.staff-tours-head {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 0.5rem;
    margin-bottom: 0.45rem;
}

.staff-tours-empty {
    padding: 0.35rem 0 0.15rem;
}

.staff-ongoing-row,
.staff-upcoming-row {
    padding: 0.45rem 0;
    border-bottom: 1px solid #e8edf3;
}

.staff-ongoing-row:last-child,
.staff-upcoming-row:last-child {
    border-bottom: 0;
    padding-bottom: 0;
}

.staff-ongoing-main,
.staff-upcoming-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 0.45rem;
    align-items: baseline;
}

.staff-ongoing-title,
.staff-upcoming-title {
    font-size: 0.92rem;
    font-weight: 800;
    line-height: 1.2;
    color: #0f172a;
    min-width: 0;
    word-break: break-word;
}

.staff-ongoing-count,
.staff-upcoming-count {
    font-size: 0.82rem;
    font-weight: 800;
    color: #334155;
    white-space: nowrap;
}

.staff-upcoming-count::after {
    content: ' pers';
    font-weight: 700;
    color: #64748b;
}

.staff-ongoing-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.2rem 0.35rem;
    margin-top: 0.15rem;
    font-size: 0.76rem;
    font-weight: 700;
    color: #64748b;
    line-height: 1.25;
}

.staff-ongoing-sep {
    color: #cbd5e1;
}

.staff-ongoing-remaining {
    color: #0f766e;
    font-weight: 800;
}

.staff-upcoming-time {
    font-size: 0.84rem;
    font-weight: 800;
    color: #0f172a;
    white-space: nowrap;
}

.staff-upcoming-row {
    grid-template-columns: 46px minmax(0, 1fr) auto;
    align-items: center;
}
</style>

<script>
    setTimeout(function () {
        window.location.reload();
    }, 30000);
</script>
@endsection
