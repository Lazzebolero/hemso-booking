@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
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

<div class="restaurant-board-grid">
    <div class="page-card">
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
                            • {{ $tour->guide?->name ?? 'Ej tilldelad' }}
                        </div>
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

    <div class="page-card">
        <div class="section-title mb-3">Kommande turer</div>

        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th style="width:90px;">Tid</th>
                        <th>Tur</th>
                        <th style="width:90px;">Antal</th>
                        <th style="width:140px;">Beräknas ut</th>
                        <th style="width:130px;">Startar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($upcomingTours as $tour)
                        <tr>
                            <td class="fw-semibold">{{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}</td>
                            <td>
                                <div class="fw-semibold">{{ $tour->title }}</div>
                                <div class="small-muted">{{ $tour->guide?->name ?? 'Ej tilldelad' }}</div>
                            </td>
                            <td>{{ $tour->booked_people_count }}</td>
                            <td>{{ $tour->estimated_end_time }}</td>
                            <td>{{ $tour->time_until_start }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="muted">Inga kommande turer idag.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.restaurant-board-grid {
    display: grid;
    grid-template-columns: minmax(340px, 0.95fr) minmax(0, 1.3fr);
    gap: 1rem;
    align-items: start;
}

.restaurant-stats-extended {
    grid-template-columns: repeat(4, minmax(0, 1fr));
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