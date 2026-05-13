@extends('layouts.app')

@section('content')
@php
    $formatTrend = function ($current, $previous, $inverse = false) {
        $value = $previous > 0 ? round((($current - $previous) / $previous) * 100) : ($current > 0 ? 100 : 0);

        if ($value > 0) {
            return [
                'icon' => $inverse ? 'bi-arrow-down-right' : 'bi-arrow-up-right',
                'class' => 'trend-good',
                'label' => ($inverse ? 'Ned ' : 'Upp ') . abs($value) . '%',
            ];
        }

        if ($value < 0) {
            return [
                'icon' => $inverse ? 'bi-arrow-up-right' : 'bi-arrow-down-right',
                'class' => 'trend-bad',
                'label' => ($inverse ? 'Upp ' : 'Ned ') . abs($value) . '%',
            ];
        }

        return [
            'icon' => 'bi-arrow-right',
            'class' => 'trend-neutral',
            'label' => 'Oförändrat',
        ];
    };

    $toursTrend = $formatTrend($stats['tours_count'], $previousStats['tours_count']);
    $bookedTrend = $formatTrend($stats['booked_people'], $previousStats['booked_people']);
    $avgTrend = $formatTrend($stats['avg_per_tour'], $previousStats['avg_per_tour']);
    $durationTrend = $formatTrend($stats['avg_duration_minutes'], $previousStats['avg_duration_minutes'], true);
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Guide: {{ $guide->name }}</h2>
        <div class="page-subtitle">
            Fördjupad statistik för vald guide.
        </div>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.statistics.guides', ['period' => $period, 'date' => $date->toDateString()]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<div class="page-card compact-card mb-4">
    <form method="GET" action="{{ route('admin.statistics.guides.show', $guide) }}" class="guide-stats-filter-grid">
        <div>
            <label class="form-label">Period</label>
            <select name="period" class="form-select">
                <option value="day" @selected($period === 'day')>Dag</option>
                <option value="week" @selected($period === 'week')>Vecka</option>
                <option value="month" @selected($period === 'month')>Månad</option>
                <option value="year" @selected($period === 'year')>År</option>
            </select>
        </div>

        <div>
            <label class="form-label">Basdatum</label>
            <input type="date" name="date" class="form-control" value="{{ $date->toDateString() }}">
        </div>

        <div>
            <label class="form-label">Vald period</label>
            <div class="stats-period-box">{{ $from->toDateString() }} – {{ $to->toDateString() }}</div>
        </div>

        <div>
            <label class="form-label">Föregående period</label>
            <div class="stats-period-box">{{ $previousFrom->toDateString() }} – {{ $previousTo->toDateString() }}</div>
        </div>

        <div class="guide-stats-filter-actions">
            <button class="btn btn-primary w-100">
                <i class="bi bi-funnel me-2"></i>Visa statistik
            </button>
        </div>
    </form>
</div>

<div class="stats-kpi-grid guide-kpi-grid mb-4">
    <div class="stats-card premium-kpi">
        <div class="stats-label">Antal turer</div>
        <div class="stats-value">{{ $stats['tours_count'] }}</div>
        <div class="stats-subtext">Föregående: {{ $previousStats['tours_count'] }}</div>
        <div class="trend-row {{ $toursTrend['class'] }}">
            <i class="bi {{ $toursTrend['icon'] }}"></i><span>{{ $toursTrend['label'] }}</span>
        </div>
    </div>

    <div class="stats-card premium-kpi">
        <div class="stats-label">Bokade personer</div>
        <div class="stats-value">{{ $stats['booked_people'] }}</div>
        <div class="stats-subtext">Föregående: {{ $previousStats['booked_people'] }}</div>
        <div class="trend-row {{ $bookedTrend['class'] }}">
            <i class="bi {{ $bookedTrend['icon'] }}"></i><span>{{ $bookedTrend['label'] }}</span>
        </div>
    </div>

    <div class="stats-card premium-kpi">
        <div class="stats-label">Snitt per tur</div>
        <div class="stats-value">{{ $stats['avg_per_tour'] }}</div>
        <div class="stats-subtext">Föregående: {{ $previousStats['avg_per_tour'] }}</div>
        <div class="trend-row {{ $avgTrend['class'] }}">
            <i class="bi {{ $avgTrend['icon'] }}"></i><span>{{ $avgTrend['label'] }}</span>
        </div>
    </div>

    <div class="stats-card premium-kpi">
        <div class="stats-label">Snittid</div>
        <div class="stats-value">{{ $stats['avg_duration_minutes'] }} min</div>
        <div class="stats-subtext">Föregående: {{ $previousStats['avg_duration_minutes'] }} min</div>
        <div class="trend-row {{ $durationTrend['class'] }}">
            <i class="bi {{ $durationTrend['icon'] }}"></i><span>{{ $durationTrend['label'] }}</span>
        </div>
    </div>

    <div class="stats-card premium-kpi">
        <div class="stats-label">Ej svenska</div>
        <div class="stats-value">{{ $stats['non_swedish_tours'] }}</div>
        <div class="stats-subtext">Turer med språk utöver svenska</div>
    </div>
</div>

<div class="guide-detail-grid mb-4">
    <div class="page-card">
        <div class="section-title mb-3">Utveckling över tid</div>
        <div class="guide-chart-box-lg">
            <canvas id="guideTimelineChart"></canvas>
        </div>
    </div>

    <div class="guide-detail-side">
        <div class="page-card">
            <div class="section-title mb-3">Turtyper</div>
            <div class="guide-chart-box-sm">
                <canvas id="guideTourTypeChart"></canvas>
            </div>
        </div>

        <div class="page-card">
            <div class="section-title mb-3">Språk</div>
            <div class="guide-chart-box-sm">
                <canvas id="guideLanguageChart"></canvas>
            </div>
        </div>
    </div>
</div>
<div class="mt-3 d-flex flex-wrap gap-2">
    @foreach($stats['tour_type_counts'] as $typeName => $count)
        @php
            $tourTypeModel = \App\Models\TourType::where('name', $typeName)->first();
        @endphp

        @if($tourTypeModel)
            <a href="{{ route('admin.statistics.guides.tour-type', ['user' => $guide->id, 'tourType' => $tourTypeModel->id, 'period' => $period, 'date' => $date->toDateString()]) }}"
               class="badge-soft badge-soft-secondary">
                {{ $typeName }}: {{ $count }}
            </a>
        @else
            <span class="badge-soft badge-soft-secondary">{{ $typeName }}: {{ $count }}</span>
        @endif
    @endforeach
</div>

<div class="page-card">
    <div class="section-title mb-3">Senaste turer</div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th style="width: 120px;">Datum</th>
                    <th style="width: 80px;">Tid</th>
                    <th>Tur</th>
                    <th style="width: 110px;">Turtyp</th>
                    <th style="width: 90px;">Bokade</th>
                    <th style="width: 90px;">Språk</th>
                    <th style="width: 90px;">Tid</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentTours as $tour)
                    <tr>
                        <td>{{ $tour->tour_date ? \Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d') : '-' }}</td>
                        <td>{{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}</td>
                        <td class="fw-semibold">{{ $tour->title }}</td>
                        <td>{{ $tour->tourType?->name ?? '-' }}</td>
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
                        <td colspan="7" class="text-center muted py-4">Ingen data för vald guide.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
.guide-stats-filter-grid {
    display: grid;
    grid-template-columns: 180px 180px minmax(220px, 1fr) minmax(220px, 1fr) 220px;
    gap: 1rem;
    align-items: end;
}
.guide-stats-filter-actions {
    display: flex;
    align-items: end;
}
.guide-kpi-grid {
    grid-template-columns: repeat(5, minmax(0, 1fr));
}
.guide-detail-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.6fr) minmax(340px, 0.9fr);
    gap: 1rem;
    align-items: start;
}
.guide-detail-side {
    display: grid;
    gap: 1rem;
}
.guide-chart-box-lg {
    height: 380px;
}
.guide-chart-box-sm {
    height: 240px;
}
.trend-row {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    margin-top: 0.7rem;
    font-size: 0.82rem;
    font-weight: 700;
}
.trend-good { color: #047857; }
.trend-bad { color: #b91c1c; }
.trend-neutral { color: #475569; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const timeline = @json($timeline);
    const tourTypeChart = @json($tourTypeChart);
    const languageChart = @json($languageChart);

    Chart.defaults.font.family = 'Figtree, system-ui, sans-serif';
    Chart.defaults.color = '#475569';

    const timelineCanvas = document.getElementById('guideTimelineChart');
    if (timelineCanvas) {
        new Chart(timelineCanvas, {
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
                        pointRadius: 0,
                        pointHoverRadius: 4
                    },
                    {
                        label: 'Bokade',
                        data: timeline.booked,
                        borderColor: '#059669',
                        backgroundColor: 'rgba(5, 150, 105, 0.12)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 3,
                        pointRadius: 0,
                        pointHoverRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.94)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0'
                    }
                },
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

    const tourTypeCanvas = document.getElementById('guideTourTypeChart');
    if (tourTypeCanvas) {
        new Chart(tourTypeCanvas, {
            type: 'bar',
            data: {
                labels: tourTypeChart.labels,
                datasets: [{
                    data: tourTypeChart.values,
                    backgroundColor: 'rgba(37, 99, 235, 0.16)',
                    borderColor: '#2563eb',
                    borderWidth: 1.5,
                    borderRadius: 10,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
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

    const languageCanvas = document.getElementById('guideLanguageChart');
    if (languageCanvas) {
        new Chart(languageCanvas, {
            type: 'doughnut',
            data: {
                labels: languageChart.labels,
                datasets: [{
                    data: languageChart.values,
                    backgroundColor: [
                        'rgba(37, 99, 235, 0.16)',
                        'rgba(5, 150, 105, 0.16)',
                        'rgba(217, 119, 6, 0.18)',
                        'rgba(220, 38, 38, 0.16)',
                        'rgba(124, 58, 237, 0.16)',
                        'rgba(100, 116, 139, 0.16)'
                    ],
                    borderColor: [
                        '#2563eb',
                        '#059669',
                        '#d97706',
                        '#dc2626',
                        '#7c3aed',
                        '#64748b'
                    ],
                    borderWidth: 1.5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
</script>
@endsection