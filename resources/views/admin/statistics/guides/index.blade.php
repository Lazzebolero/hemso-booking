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

    $toursTrend = $formatTrend($summary['tours_count'], $previousSummary['tours_count']);
    $bookedTrend = $formatTrend($summary['booked_people'], $previousSummary['booked_people']);
    $avgTrend = $formatTrend($summary['avg_per_tour'], $previousSummary['avg_per_tour']);
    $durationTrend = $formatTrend($summary['avg_duration_minutes'], $previousSummary['avg_duration_minutes'], true);
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Guidestatistik</h2>
        <div class="page-subtitle">Översikt, ranking, språkandelar och klickbar fördjupning per guide.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.statistics.guides.export', ['period' => $period, 'date' => $date->toDateString()]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-download me-2"></i>CSV-export
        </a>
    </div>
</div>

<div class="page-card compact-card mb-4">
    <form method="GET" action="{{ route('admin.statistics.guides') }}" class="guide-stats-filter-grid">
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
        <div class="stats-value">{{ $summary['tours_count'] }}</div>
        <div class="stats-subtext">Föregående: {{ $previousSummary['tours_count'] }}</div>
        <div class="trend-row {{ $toursTrend['class'] }}">
            <i class="bi {{ $toursTrend['icon'] }}"></i><span>{{ $toursTrend['label'] }}</span>
        </div>
    </div>

    <div class="stats-card premium-kpi">
        <div class="stats-label">Bokade personer</div>
        <div class="stats-value">{{ $summary['booked_people'] }}</div>
        <div class="stats-subtext">Föregående: {{ $previousSummary['booked_people'] }}</div>
        <div class="trend-row {{ $bookedTrend['class'] }}">
            <i class="bi {{ $bookedTrend['icon'] }}"></i><span>{{ $bookedTrend['label'] }}</span>
        </div>
    </div>

    <div class="stats-card premium-kpi">
        <div class="stats-label">Snitt per tur</div>
        <div class="stats-value">{{ $summary['avg_per_tour'] }}</div>
        <div class="stats-subtext">Föregående: {{ $previousSummary['avg_per_tour'] }}</div>
        <div class="trend-row {{ $avgTrend['class'] }}">
            <i class="bi {{ $avgTrend['icon'] }}"></i><span>{{ $avgTrend['label'] }}</span>
        </div>
    </div>

    <div class="stats-card premium-kpi">
        <div class="stats-label">Snittid per tur</div>
        <div class="stats-value">{{ $summary['avg_duration_minutes'] }} min</div>
        <div class="stats-subtext">Föregående: {{ $previousSummary['avg_duration_minutes'] }} min</div>
        <div class="trend-row {{ $durationTrend['class'] }}">
            <i class="bi {{ $durationTrend['icon'] }}"></i><span>{{ $durationTrend['label'] }}</span>
        </div>
    </div>

    <div class="stats-card premium-kpi">
        <div class="stats-label">Ej svenska</div>
        <div class="stats-value">{{ $summary['non_swedish_tours'] }}</div>
        <div class="stats-subtext">Turer med språk utöver svenska</div>
    </div>
</div>

<div class="guide-v4-top-grid mb-4">
    <div class="page-card">
        <div class="section-title mb-3">Bästa guide i perioden</div>

        @if($bestGuide)
            <a href="{{ route('admin.statistics.guides.show', ['user' => $bestGuide['guide_id'], 'period' => $period, 'date' => $date->toDateString()]) }}" class="guide-best-card">
                <div>
                    <div class="guide-best-name">{{ $bestGuide['guide_name'] }}</div>
                    <div class="small-muted">{{ $bestGuide['tours_count'] }} turer • {{ $bestGuide['booked_people'] }} bokade</div>
                </div>

                <div class="guide-best-stats">
                    <div class="info-item">
                        <div class="info-label">Snitt/tur</div>
                        <div class="info-value">{{ $bestGuide['avg_per_tour'] }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Snittid</div>
                        <div class="info-value">{{ $bestGuide['avg_duration_minutes'] }} min</div>
                    </div>
                </div>
            </a>
        @else
            <div class="muted">Ingen guide har data för vald period.</div>
        @endif
    </div>

    <div class="guide-ranking-stack">
        <div class="page-card">
            <div class="section-title mb-3">Topp 5 – flest turer</div>
            @forelse($rankingByTours as $index => $row)
                <div class="ranking-row">
                    <span class="ranking-index">{{ $index + 1 }}</span>
                    <span class="ranking-name">{{ $row['guide_name'] }}</span>
                    <span class="ranking-value">{{ $row['tours_count'] }}</span>
                </div>
            @empty
                <div class="muted">Ingen data.</div>
            @endforelse
        </div>

        <div class="page-card">
            <div class="section-title mb-3">Topp 5 – flest bokade</div>
            @forelse($rankingByBooked as $index => $row)
                <div class="ranking-row">
                    <span class="ranking-index">{{ $index + 1 }}</span>
                    <span class="ranking-name">{{ $row['guide_name'] }}</span>
                    <span class="ranking-value">{{ $row['booked_people'] }}</span>
                </div>
            @empty
                <div class="muted">Ingen data.</div>
            @endforelse
        </div>

        <div class="page-card">
            <div class="section-title mb-3">Topp 5 – högst snitt/tur</div>
            @forelse($rankingByAverage as $index => $row)
                <div class="ranking-row">
                    <span class="ranking-index">{{ $index + 1 }}</span>
                    <span class="ranking-name">{{ $row['guide_name'] }}</span>
                    <span class="ranking-value">{{ $row['avg_per_tour'] }}</span>
                </div>
            @empty
                <div class="muted">Ingen data.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="guide-stats-chart-grid mb-4">
    <div class="page-card">
        <div class="section-title mb-3">Turer per guide</div>
        <div class="guide-chart-box"><canvas id="guideToursChart"></canvas></div>
    </div>

    <div class="page-card">
        <div class="section-title mb-3">Bokade per guide</div>
        <div class="guide-chart-box"><canvas id="guideBookedChart"></canvas></div>
    </div>

    <div class="page-card">
        <div class="section-title mb-3">Snitt per tur</div>
        <div class="guide-chart-box"><canvas id="guideAverageChart"></canvas></div>
    </div>

    <div class="page-card">
        <div class="section-title mb-3">Snittid per guide</div>
        <div class="guide-chart-box"><canvas id="guideDurationChart"></canvas></div>
    </div>
</div>

<div class="guide-v4-bottom-grid mb-4">
    <div class="page-card">
        <div class="section-title mb-3">Språkandelar i perioden</div>
        <div class="guide-language-share-list">
            @forelse($languageShare as $row)
                <div class="guide-language-share-row">
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
        <div class="section-title mb-3">Guider</div>

        <div class="guide-cards-grid">
            @forelse($guideRows as $row)
                @php
                    $rowTrend = $formatTrend($row['booked_people'], $row['previous_booked_people']);
                @endphp

                <a href="{{ route('admin.statistics.guides.show', ['user' => $row['guide_id'], 'period' => $period, 'date' => $date->toDateString()]) }}"
                   class="guide-stat-card">
                    <div class="guide-stat-card-top">
                        <div>
                            <div class="guide-stat-name">{{ $row['guide_name'] }}</div>
                            <div class="small-muted">{{ $row['tours_count'] }} turer • {{ $row['booked_people'] }} bokade</div>
                        </div>

                        <div class="trend-row {{ $rowTrend['class'] }}" style="margin-top:0;">
                            <i class="bi {{ $rowTrend['icon'] }}"></i>
                            <span>{{ $rowTrend['label'] }}</span>
                        </div>
                    </div>

                    <div class="guide-stat-mini-grid">
                        <div class="info-item">
                            <div class="info-label">Snitt/tur</div>
                            <div class="info-value">{{ $row['avg_per_tour'] }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Snittid</div>
                            <div class="info-value">{{ $row['avg_duration_minutes'] }} min</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Ej svenska</div>
                            <div class="info-value">{{ $row['non_swedish_tours'] }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Turtyper</div>
                            <div class="info-value">{{ $row['tour_type_counts']->count() }}</div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="muted">Ingen guidedata för vald period.</div>
            @endforelse
        </div>
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
.guide-v4-top-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(360px, 0.9fr);
    gap: 1rem;
    align-items: start;
}
.guide-ranking-stack {
    display: grid;
    gap: 1rem;
}
.guide-best-card {
    display: block;
    color: inherit;
    background: linear-gradient(180deg, #fbfdff 0%, #ffffff 100%);
    border: 1px solid var(--brand-line-soft);
    border-radius: 16px;
    padding: 1rem;
}
.guide-best-name {
    font-size: 1.1rem;
    font-weight: 800;
    margin-bottom: 0.2rem;
}
.guide-best-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-top: 0.9rem;
}
.ranking-row {
    display: grid;
    grid-template-columns: 28px 1fr auto;
    gap: 0.7rem;
    align-items: center;
    padding: 0.45rem 0;
    border-bottom: 1px solid var(--brand-line-soft);
}
.ranking-row:last-child {
    border-bottom: none;
}
.ranking-index {
    font-weight: 800;
    color: var(--text-soft);
}
.ranking-name {
    font-weight: 700;
}
.ranking-value {
    font-weight: 800;
}
.guide-stats-chart-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
.guide-chart-box {
    height: 300px;
}
.guide-v4-bottom-grid {
    display: grid;
    grid-template-columns: 360px minmax(0, 1fr);
    gap: 1rem;
    align-items: start;
}
.guide-language-share-list {
    display: grid;
    gap: 0.8rem;
}
.guide-cards-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
.guide-stat-card {
    display: block;
    color: inherit;
    background: linear-gradient(180deg, #fbfdff 0%, #ffffff 100%);
    border: 1px solid var(--brand-line-soft);
    border-radius: 16px;
    padding: 1rem;
    box-shadow: var(--shadow-card);
}
.guide-stat-card:hover {
    background: #ffffff;
    border-color: #d3deea;
}
.guide-stat-card-top {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: start;
    margin-bottom: 0.85rem;
}
.guide-stat-name {
    font-size: 1rem;
    font-weight: 800;
}
.guide-stat-mini-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
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
    const guideChartData = @json($chartData);

    Chart.defaults.font.family = 'Figtree, system-ui, sans-serif';
    Chart.defaults.color = '#475569';

    function renderBarChart(id, data, label, suffix = '') {
        const canvas = document.getElementById(id);
        if (!canvas) return;

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: guideChartData.labels,
                datasets: [{
                    label,
                    data,
                    backgroundColor: 'rgba(37, 99, 235, 0.16)',
                    borderColor: '#2563eb',
                    borderWidth: 1.5,
                    borderRadius: 10,
                    borderSkipped: false,
                    maxBarThickness: 38
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.94)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        callbacks: {
                            label: function(context) {
                                return context.raw + suffix;
                            }
                        }
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

    renderBarChart('guideToursChart', guideChartData.tours, 'Turer');
    renderBarChart('guideBookedChart', guideChartData.booked, 'Bokade');
    renderBarChart('guideAverageChart', guideChartData.avgPerTour, 'Snitt per tur');
    renderBarChart('guideDurationChart', guideChartData.avgDuration, 'Snittid', ' min');
</script>
@endsection