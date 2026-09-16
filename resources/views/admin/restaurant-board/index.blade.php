@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
    $restaurantBoardRoute = \App\Support\ActiveRole::routeName('restaurant-board');
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Restaurang – dagens turer</h2>
        <div class="page-subtitle">
            Översikt för restaurangen. Uppdateras automatiskt var 30:e sekund.
        </div>
    </div>

    <div class="page-actions">
        <a href="{{ route(\App\Support\ActiveRole::routeName('restaurant-board.kiosk')) }}" target="_blank" class="btn btn-primary">
            <i class="bi bi-box-arrow-up-right me-2"></i>Öppna helskärm
        </a>
    </div>
</div>

<div class="stats-grid mb-4 restaurant-stats-extended">
    <div class="stats-card">
        <div class="stats-label">Pågående turer</div>
        <div class="stats-value">{{ $ongoingTours->count() }}</div>
        <div class="stats-subtext">Antal startade men ej avslutade turer</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Gäster på pågående turer</div>
        <div class="stats-value">{{ $totalOngoingGuests }}</div>
        <div class="stats-subtext">Totalt antal gäster ute på tur just nu</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Män på tur</div>
        <div class="stats-value">{{ $ongoingParticipantBreakdown['men'] ?? 0 }}</div>
        <div class="stats-subtext">Startade men ej avslutade turer</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Kvinnor på tur</div>
        <div class="stats-value">{{ $ongoingParticipantBreakdown['women'] ?? 0 }}</div>
        <div class="stats-subtext">Startade men ej avslutade turer</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Ungdomar på tur</div>
        <div class="stats-value">{{ $ongoingParticipantBreakdown['youth'] ?? 0 }}</div>
        <div class="stats-subtext">Startade men ej avslutade turer</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Barn på tur</div>
        <div class="stats-value">{{ $ongoingParticipantBreakdown['children'] ?? 0 }}</div>
        <div class="stats-subtext">Startade men ej avslutade turer</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Gäster på kommande turer</div>
        <div class="stats-value">{{ $totalUpcomingGuests }}</div>
        <div class="stats-subtext">Totalt antal gäster på turer som ännu inte startat</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Senast uppdaterad</div>
        <div class="stats-value" style="font-size:1.2rem;">{{ $nowLabel }}</div>
        <div class="stats-subtext">Auto-refresh var 30:e sekund</div>
    </div>
</div>

@include('partials.ferry.status-card', [
    'snapshot' => $ferrySnapshot ?? [],
    'timetableUrl' => $ferryTimetableUrl ?? null,
    'variant' => 'app',
])

<div class="restaurant-board-layout mb-4">
    <div class="restaurant-board-grid">
        <div class="page-card restaurant-board-panel">
            <div class="section-title mb-3">Pågående turer</div>

            @forelse($ongoingTours as $tour)
                <div class="restaurant-tour-card">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="restaurant-tour-title">{{ $tour->title }}</div>
                            <div class="small-muted">
                                @if(!empty($tour->started_at))
                                    Turen startade {{ \Carbon\Carbon::parse($tour->started_at)->format('H:i') }}
                                @else
                                    Start {{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}
                                @endif
                            </div>
                            @include('partials.admin.tour-guide-display-block', ['tour' => $tour])
                        </div>

                        <span class="badge-soft badge-soft-success">Pågående</span>
                    </div>

                    <div class="restaurant-metrics">
                        <div class="restaurant-metric">
                            <div class="info-label">Bokade</div>
                            <div class="info-value">{{ $tour->booked_people_count }}</div>
                        </div>

                        <div class="restaurant-metric">
                            <div class="info-label">Beräknas klar</div>
                            <div class="info-value">{{ $tour->estimated_end_time }}</div>
                        </div>

                        <div class="restaurant-metric">
                            <div class="info-label">Tid kvar</div>
                            <div class="info-value">{{ $tour->remaining_to_end }}</div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="muted">Inga pågående turer just nu.</div>
            @endforelse
        </div>

        <div class="page-card restaurant-board-panel restaurant-upcoming-panel">
            <div class="section-title mb-2">Kommande turer idag</div>
            <div class="small-muted mb-3">Planerade turer kvar idag som inte har startat ännu.</div>

            @includeIf('partials.admin.restaurant-upcoming-tours-table', [
                'tours' => $upcomingToursToday ?? collect(),
                'showTourDate' => false,
                'useAppTable' => true,
                'emptyMessage' => 'Inga fler kommande turer idag.',
            ])

            <div class="restaurant-upcoming-divider"></div>

            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                <div>
                    <div class="section-title mb-1">Kommande turer</div>
                    <div class="small-muted">
                        Imorgon och de närmaste {{ $aheadDays ?? 7 }} dagarna
                        @if(!empty($aheadEndDate))
                            (t.o.m. {{ \Carbon\Carbon::parse($aheadEndDate)->format('Y-m-d') }}).
                        @endif
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a
                        href="{{ route($restaurantBoardRoute, ['ahead_days' => 7]) }}"
                        class="btn btn-sm {{ ($aheadDays ?? 7) === 7 ? 'btn-primary' : 'btn-outline-secondary' }}"
                    >
                        7 dagar
                    </a>

                    <a
                        href="{{ route($restaurantBoardRoute, ['ahead_days' => 30]) }}"
                        class="btn btn-sm {{ ($aheadDays ?? 7) === 30 ? 'btn-primary' : 'btn-outline-secondary' }}"
                    >
                        30 dagar
                    </a>
                </div>
            </div>

            @includeIf('partials.admin.restaurant-upcoming-tours-table', [
                'tours' => $upcomingToursAhead ?? collect(),
                'showTourDate' => true,
                'useAppTable' => true,
                'emptyMessage' => 'Inga kommande turer de valda dagarna.',
            ])
        </div>
    </div>

    <div class="page-card restaurant-staff-panel restaurant-board-panel">
        <div class="section-title mb-3">Personal idag</div>

        @forelse($todayStaffByFunction as $functionKey => $shifts)
            <div class="restaurant-function-group">
                <div class="restaurant-function-title">
                    {{ $restaurantFunctions[$functionKey] ?? ucfirst($functionKey) }}
                </div>

                @foreach($shifts as $shift)
                    <div class="restaurant-staff-item">
                        <div class="fw-semibold">{{ $shift->user->name ?? 'Okänd' }}</div>
                        <div class="small-muted">
                            {{ substr($shift->start_time, 0, 5) }}–{{ $shift->end_time ? substr($shift->end_time, 0, 5) : '--:--' }}
                        </div>
                    </div>
                @endforeach
            </div>
        @empty
            <div class="muted">Ingen restaurangpersonal schemalagd idag.</div>
        @endforelse
    </div>
</div>

<div class="page-card mb-4">
    <div class="section-title mb-1">Dagens turer</div>
    <div class="small-muted mb-3">Kompakt översikt över dagens schema.</div>

    @includeIf('partials.admin.restaurant-today-tours-table', [
        'tours' => $todayTours ?? collect(),
        'useAppTable' => true,
    ])
</div>

<style>
.restaurant-board-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.55fr) minmax(320px, 0.9fr);
    gap: 1rem;
    align-items: start;
}

.restaurant-board-grid {
    display: grid;
    grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.3fr);
    gap: 1rem;
    align-items: start;
    min-width: 0;
}

.restaurant-board-layout > .page-card,
.restaurant-board-grid > .page-card {
    min-width: 0;
}

.restaurant-board-grid > .page-card + .page-card,
.restaurant-board-layout > .page-card {
    margin-top: 0;
}

.restaurant-upcoming-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.restaurant-upcoming-table--app {
    font-size: 0.92rem;
}

.restaurant-upcoming-table thead th {
    text-align: left;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-soft);
    padding: 0.55rem 0.35rem;
    border-bottom: 1px solid var(--brand-line-soft, #e2e8f0);
    vertical-align: bottom;
}

.restaurant-upcoming-table tbody td {
    padding: 0.65rem 0.35rem;
    border-bottom: 1px solid #edf2f7;
    vertical-align: top;
    word-break: break-word;
}

.restaurant-upcoming-table tbody tr:last-child td {
    border-bottom: none;
}

.restaurant-occupancy-bar {
    width: 100%;
    max-width: 72px;
    height: 8px;
    border-radius: 999px;
    overflow: hidden;
    background: #e2e8f0;
}

.restaurant-occupancy-bar > div {
    height: 100%;
    border-radius: 999px;
}

.restaurant-upcoming-panel {
    overflow: hidden;
}

.restaurant-upcoming-divider {
    margin: 1rem 0;
    border-top: 1px solid var(--brand-line-soft, #e2e8f0);
}

.restaurant-stats-extended {
    grid-template-columns: repeat(4, minmax(0, 1fr));
}

.restaurant-staff-panel {
    align-self: start;
}

.restaurant-function-group {
    margin-top: 1rem;
    border-top: 1px solid var(--brand-line-soft, #e2e8f0);
    padding-top: 1rem;
}

.restaurant-function-group:first-child {
    margin-top: 0;
    border-top: 0;
    padding-top: 0;
}

.restaurant-function-title {
    font-size: 0.95rem;
    font-weight: 800;
    margin-bottom: 0.65rem;
    color: var(--brand-primary, #0f766e);
}

.restaurant-staff-item {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%);
    padding: 0.85rem;
    margin-bottom: 0.65rem;
}

.restaurant-staff-item:last-child {
    margin-bottom: 0;
}

.restaurant-tour-card {
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%);
    padding: 1rem;
    margin-bottom: 0.9rem;
}

.restaurant-tour-card:last-child {
    margin-bottom: 0;
}

.restaurant-tour-title {
    font-size: 1.02rem;
    font-weight: 800;
    line-height: 1.25;
}

.restaurant-metrics {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.75rem;
    margin-top: 0.9rem;
}

.restaurant-metric {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 0.85rem;
}

.info-label {
    font-size: 0.76rem;
    color: var(--text-soft);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 800;
    margin-bottom: 0.25rem;
}

.info-value {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--text-main);
}

@media (max-width: 1350px) {
    .restaurant-board-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 1200px) {
    .restaurant-stats-extended {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .restaurant-board-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 700px) {
    .restaurant-stats-extended {
        grid-template-columns: 1fr;
    }

    .restaurant-metrics {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
    setTimeout(function () {
        window.location.reload();
    }, 30000);
</script>
@endsection
