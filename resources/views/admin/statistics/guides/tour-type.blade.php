@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">{{ $guide->name }} – {{ $tourType->name }}</h2>
        <div class="page-subtitle">Fördjupning per guide och turtyp.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.statistics.guides.show', ['user' => $guide->id, 'period' => $period, 'date' => $date->toDateString()]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka till guide
        </a>
    </div>
</div>

<div class="stats-grid mb-4">
    <div class="stats-card">
        <div class="stats-label">Turer</div>
        <div class="stats-value">{{ $stats['tours_count'] }}</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Bokade</div>
        <div class="stats-value">{{ $stats['booked_people'] }}</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Snitt/tur</div>
        <div class="stats-value">{{ $stats['avg_per_tour'] }}</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Snittid</div>
        <div class="stats-value">{{ $stats['avg_duration_minutes'] }} min</div>
    </div>
</div>

<div class="guide-detail-grid mb-4">
    <div class="page-card">
        <div class="section-title mb-3">Utveckling över tid</div>
        <div class="guide-chart-box-lg">
            <canvas id="guideTimelineChart"></canvas>
        </div>
    </div>

    <div class="page-card">
        <div class="section-title mb-3">Språkandelar</div>

        @forelse($languageShare as $row)
            <div class="mb-3">
                <div class="d-flex justify-content-between small mb-1">
                    <span>{{ $row['label'] }}</span>
                    <span>{{ $row['count'] }} • {{ $row['percent'] }}%</span>
                </div>
                <div class="progress-modern">
                    <div style="width: {{ min(100, $row['percent']) }}%; background: var(--brand-accent);"></div>
                </div>
            </div>
        @empty
            <div class="muted">Ingen språkdata.</div>
        @endforelse
    </div>
</div>

<div class="page-card">
    <div class="section-title mb-3">Senaste turer</div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th style="width:120px;">Datum</th>
                    <th style="width:80px;">Tid</th>
                    <th>Tur</th>
                    <th style="width:90px;">Bokade</th>
                    <th style="width:90px;">Språk</th>
                    <th style="width:90px;">Tid</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentTours as $tour)
                    <tr>
                        <td>{{ $tour->tour_date ? \Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d') : '-' }}</td>
                        <td>{{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}</td>
                        <td class="fw-semibold">{{ $tour->title }}</td>
                        <td>{{ $tour->booked_people_count }}</td>
                        <td>
                        @if(empty($tour->language_codes))
								SV
						@else
							{{ implode(' + ', $tour->language_codes) }}
						@endif
                        </td>
                        <td>{{ $tour->resolved_duration_minutes }} min</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center muted py-4">Ingen data för vald turtyp.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
.guide-detail-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.5fr) 360px;
    gap: 1rem;
}
.guide-chart-box-lg {
    height: 380px;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const timeline = @json($timeline);

    const canvas = document.getElementById('guideTimelineChart');
    if (canvas) {
        new Chart(canvas, {
            type: 'line',
            data: {
                labels: timeline.labels,
                datasets: [
                    {
                        label: 'Turer',
                        data: timeline.tours,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.14)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 3,
                        pointRadius: 0
                    },
                    {
                        label: 'Bokade',
                        data: timeline.booked,
                        borderColor: '#059669',
                        backgroundColor: 'rgba(5, 150, 105, 0.12)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 3,
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.14)' }
                    }
                }
            }
        });
    }
</script>
@endsection