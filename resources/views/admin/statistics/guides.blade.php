@extends('layouts.app')

@section('content')
@php
    $formatTrend = function ($current, $previous, $inverse = false) {
        $value = $previous > 0 ? round((($current - $previous) / $previous) * 100) : ($current > 0 ? 100 : 0);

        if ($value > 0) {
            return [
                'icon' => $inverse ? 'bi-arrow-down-right' : 'bi-arrow-up-right',
                'class' => $inverse ? 'trend-good' : 'trend-good',
                'label' => ($inverse ? 'Ned ' : 'Upp ') . abs($value) . '%',
            ];
        }

        if ($value < 0) {
            return [
                'icon' => $inverse ? 'bi-arrow-up-right' : 'bi-arrow-down-right',
                'class' => $inverse ? 'trend-bad' : 'trend-bad',
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
        <div class="page-subtitle">
            Produktion, språk, turtyper och snittider per guide med jämförelse mot föregående period.
        </div>
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
            <div class="stats-period-box">
                {{ $from->toDateString() }} – {{ $to->toDateString() }}
            </div>
        </div>

        <div>
            <label class="form-label">Föregående period</label>
            <div class="stats-period-box">
                {{ $previousFrom->toDateString() }} – {{ $previousTo->toDateString() }}
            </div>
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
        <div class="stats-subtext">Föregående period: {{ $previousSummary['tours_count'] }}</div>
        <div class="trend-row {{ $toursTrend['class'] }}">
            <i class="bi {{ $toursTrend['icon'] }}"></i>
            <span>{{ $toursTrend['label'] }}</span>
        </div>
    </div>

    <div class="stats-card premium-kpi">
        <div class="stats-label">Bokade personer</div>
        <div class="stats-value">{{ $summary['booked_people'] }}</div>
        <div class="stats-subtext">Föregående period: {{ $previousSummary['booked_people'] }}</div>
        <div class="trend-row {{ $bookedTrend['class'] }}">
            <i class="bi {{ $bookedTrend['icon'] }}"></i>
            <span>{{ $bookedTrend['label'] }}</span>
        </div>
    </div>

    <div class="stats-card premium-kpi">
        <div class="stats-label">Snitt per tur</div>
        <div class="stats-value">{{ $summary['avg_per_tour'] }}</div>
        <div class="stats-subtext">Föregående period: {{ $previousSummary['avg_per_tour'] }}</div>
        <div class="trend-row {{ $avgTrend['class'] }}">
            <i class="bi {{ $avgTrend['icon'] }}"></i>
            <span>{{ $avgTrend['label'] }}</span>
        </div>
    </div>

    <div class="stats-card premium-kpi">
        <div class="stats-label">Snittid per tur</div>
        <div class="stats-value">{{ $summary['avg_duration_minutes'] }} min</div>
        <div class="stats-subtext">Föregående period: {{ $previousSummary['avg_duration_minutes'] }} min</div>
        <div class="trend-row {{ $durationTrend['class'] }}">
            <i class="bi {{ $durationTrend['icon'] }}"></i>
            <span>{{ $durationTrend['label'] }}</span>
        </div>
    </div>

    <div class="stats-card premium-kpi">
        <div class="stats-label">Ej svenska</div>
        <div class="stats-value">{{ $summary['non_swedish_tours'] }}</div>
        <div class="stats-subtext">Turer med språk utöver svenska</div>
    </div>
</div>

<div class="guide-stats-chart-grid mb-4">
    <div class="page-card">
        <div class="section-title mb-3">Turer per guide</div>
        <div class="guide-chart-box">
            <canvas id="guideToursChart"></canvas>
        </div>
    </div>

    <div class="page-card">
        <div class="section-title mb-3">Bokade personer per guide</div>
        <div class="guide-chart-box">
            <canvas id="guideBookedChart"></canvas>
        </div>
    </div>

    <div class="page-card">
        <div class="section-title mb-3">Snitt per tur</div>
        <div class="guide-chart-box">
            <canvas id="guideAverageChart"></canvas>
        </div>
    </div>

    <div class="page-card">
        <div class="section-title mb-3">Snittid per guide</div>
        <div class="guide-chart-box">
            <canvas id="guideDurationChart"></canvas>
        </div>
    </div>
</div>

<div class="page-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <div class="section-title mb-1">Per guide</div>
            <div class="small-muted">Produktion, språk och faktisk eller beräknad turtid per guide.</div>
        </div>
    </div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Guide</th>
                    <th style="width: 90px;">Turer</th>
                    <th style="width: 120px;">Bokade</th>
                    <th style="width: 100px;">Snitt/tur</th>
                    <th style="width: 110px;">Snittid</th>
                    <th style="width: 110px;">Ej svenska</th>
                    <th>Turtyper</th>
                    <th style="width: 130px;">Bokade trend</th>
                </tr>
            </thead>
            <tbody>
                @forelse($guideRows as $row)
                    @php
                        $rowTrend = $formatTrend($row['booked_people'], $row['previous_booked_people']);
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $row['guide_name'] }}</div>
                        </td>
                        <td>{{ $row['tours_count'] }}</td>
                        <td>{{ $row['booked_people'] }}</td>
                        <td>{{ $row['avg_per_tour'] }}</td>
                        <td>{{ $row['avg_duration_minutes'] }} min</td>
                        <td>{{ $row['non_swedish_tours'] }}</td>
                        <td>
                            <div class="guide-type-badges">
                                @forelse($row['tour_type_counts'] as $type => $count)
                                    <span class="badge-soft badge-soft-secondary">{{ $type }}: {{ $count }}</span>
                                @empty
                                    <span class="small-muted">Ingen data</span>
                                @endforelse
                            </div>
                        </td>
                        <td>
                            <div class="trend-row {{ $rowTrend['class'] }}" style="margin-top:0;">
                                <i class="bi {{ $rowTrend['icon'] }}"></i>
                                <span>{{ $rowTrend['label'] }}</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center muted py-4">Ingen guidestatistik för vald period.</td>
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
.guide-stats-chart-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
.guide-chart-box {
    height: 300px;
}
.guide-type-badges {
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
}
.trend-row {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    margin-top: 0.7rem;
    font-size: 0.82rem;
    font-weight: 700;
}
.trend-good {
    color: #047857;
}
.trend-bad {
    color: #b91c1c;
}
.trend-neutral {
    color: #475569;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const guideChartData = @json($chartData);

    Chart.defaults.font.family = 'Figtree, system-ui, sans-serif';
    Chart.defaults.color = '#475569';

    function renderBarChart(id, label, data, suffix = '') {
        const canvas = document.getElementById(id);
        if (!canvas) return;

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: guideChartData.labels,
                datasets: [{
                    label: label,
                    data: data,
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
                layout: {
                    padding: {
                        top: 16
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
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
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(148, 163, 184, 0.14)'
                        }
                    }
                }
            }
        });
    }

    renderBarChart('guideToursChart', 'Turer', guideChartData.tours);
    renderBarChart('guideBookedChart', 'Bokade personer', guideChartData.booked);
    renderBarChart('guideAverageChart', 'Snitt per tur', guideChartData.avgPerTour);
    renderBarChart('guideDurationChart', 'Snittid', guideChartData.avgDuration, ' min');
</script>
@endsection