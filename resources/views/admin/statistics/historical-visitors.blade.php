@extends('layouts.app')

@section('content')
@php
    $baselineLabel = implode(' och ', $baselineYears);
@endphp
<div class="page-header">
    <div>
        <h2 class="page-title">Historisk besöksstatistik</h2>
        <div class="page-subtitle">
            Historik från {{ $baselineLabel }} (samma ISO-vecka och veckodag).
            Jämförelse per veckodag i samma veckonummer (ISO) mellan {{ $comparisonYear }}, {{ $baselineLabel }}.
            Prognos är medelvärdet av {{ $baselineLabel }}.
            Väder från SMHI Lungö A matchas på samma sätt (ISO-vecka + veckodag) för alla år. Väder för innevarande år visas från och med dagen efter, när dygnet är komplett.
        </div>
    </div>

    <div class="page-actions d-flex gap-2 flex-wrap">
        @if(Route::has(\App\Support\ActiveRole::routePrefix() . '.statistics-notes.edit'))
            <a href="{{ route(\App\Support\ActiveRole::routePrefix() . '.statistics-notes.edit', ['date' => ($to ?? now())->toDateString()]) }}" class="btn btn-outline-secondary">
                <i class="bi bi-journal-text me-2"></i>Notera denna dag
            </a>
        @endif
        <a href="{{ route('admin.statistics.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-bar-chart me-2"></i>Övrig statistik
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="page-card compact-card mb-4">
    @include('partials.admin.statistics-period-filter', [
        'formAction' => route('admin.statistics.historical-visitors.index'),
        'formId' => 'historical-visitors-filter-form',
        'period' => $period,
        'date' => $date,
        'from' => $from,
        'to' => $to,
        'year' => $year,
        'month' => $month,
        'submitLabel' => 'Visa',
        'showChartToggle' => true,
        'showChart' => $showChart ?? true,
    ])
</div>

@if(($showChart ?? false) && count($series ?? []) > 0)
    <div class="page-card mb-4">
        <div class="mb-3">
            <h3 class="h5 mb-1">Linjediagram – vald period</h3>
            <p class="small-muted mb-0">
                {{ $from->toDateString() }} – {{ $to->toDateString() }}.
                Historik {{ $baselineLabel }}, prognos och bokade {{ $comparisonYear }} per dag.
            </p>
        </div>
        <div class="historical-chart-shell">
            <div class="historical-chart-canvas">
                <canvas id="historicalPeriodChart"></canvas>
            </div>
        </div>
    </div>
@endif

<div class="page-card mb-4">
    <h3 class="h5 mb-2">Jämförelse – passerade dagar</h3>
    <p class="small-muted mb-3">
        Samma veckonummer och veckodag (ISO) för {{ $comparisonYear }}, {{ $baselineLabel }}.
        Datumet visar innevarande års kalenderdag, t.ex. måndag 26 maj (26/5).
        Väderkolumnerna visar Lungö A för motsvarande ISO-vecka och veckodag i respektive år. Idag utan väder — sparas vid nattlig synk.
        Turer och guider avser {{ $comparisonYear }}: antal ej avbokade turer och unika guider (huvudguide + medguider) den dagen.
    </p>

    <div class="stats-kpi-grid mb-4">
        <div class="stats-card premium-kpi">
            <div class="stats-label">Bokade {{ $comparisonYear }}</div>
            <div class="stats-value">{{ $comparisonSummary['current'] ?? 0 }}</div>
            <div class="stats-subtext">{{ $comparisonSummary['days'] ?? 0 }} passerade dagar</div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Turer {{ $comparisonYear }}</div>
            <div class="stats-value">{{ $comparisonSummary['tours'] ?? 0 }}</div>
            <div class="stats-subtext">Ej avbokade turer</div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Guider {{ $comparisonYear }}</div>
            <div class="stats-value">{{ $comparisonSummary['guide_days'] ?? 0 }}</div>
            <div class="stats-subtext">Summa unika guider per dag</div>
        </div>
        @foreach($baselineYears as $year)
            <div class="stats-card premium-kpi">
                <div class="stats-label">Historik {{ $year }}</div>
                <div class="stats-value">{{ $comparisonSummary["historical_{$year}"] ?? 0 }}</div>
                <div class="stats-subtext">Samma vecka + veckodag</div>
            </div>
        @endforeach
    </div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Dag</th>
                    <th>V</th>
                    @foreach($baselineYears as $year)
                        <th>{{ $year }}</th>
                        <th>Väder {{ $year }}</th>
                    @endforeach
                    <th>Väder {{ $comparisonYear }}</th>
                    <th>Bokade</th>
                    <th>Turer</th>
                    <th>Guider</th>
                    <th>Notering</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pastSeries as $row)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $row['label'] }}</div>
                            <div class="small-muted">{{ $row['label_short'] }}</div>
                        </td>
                        <td>{{ $row['iso_week'] }}</td>
                        @foreach($baselineYears as $year)
                            <td>{{ $row['historical_by_year'][$year] ?? '–' }}</td>
                            <td class="small-muted">
                                @include('partials.admin.weather-summary', [
                                    'summary' => $row['weather_by_year'][$year] ?? null,
                                ])
                            </td>
                        @endforeach
                        <td class="small-muted">
                            @include('partials.admin.weather-summary', [
                                'summary' => $row['weather_current'] ?? null,
                            ])
                        </td>
                        <td>{{ $row['current'] }}</td>
                        <td>{{ $row['tours_count'] ?? 0 }}</td>
                        <td>{{ $row['guides_count'] ?? 0 }}</td>
                        <td class="small-muted">
                            @if(! empty($row['day_note']))
                                <span title="{{ $row['day_note'] }}">{{ \Illuminate\Support\Str::limit($row['day_note'], 60) }}</span>
                            @else
                                –
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 7 + (count($baselineYears) * 2) }}" class="text-center small-muted py-4">
                            Inga passerade dagar i vald period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="page-card mb-4">
    <h3 class="h5 mb-2">Prognos</h3>
    <p class="small-muted mb-3">
        Medel av {{ $baselineLabel }} för samma veckonummer och veckodag (ISO) som raden visar.
        Väderkolumnerna visar hur det var på motsvarande ISO-dag tidigare år (historisk kontext, inte prognos).
        Turer och guider avser {{ $comparisonYear }}.
    </p>

    <div class="stats-kpi-grid mb-4">
        <div class="stats-card premium-kpi">
            <div class="stats-label">Prognos totalt (period)</div>
            <div class="stats-value">{{ $forecastSummary['forecast'] ?? 0 }}</div>
            <div class="stats-subtext">{{ $forecastSummary['days'] ?? 0 }} dagar</div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Bokade totalt (period)</div>
            <div class="stats-value">{{ collect($series)->sum('current') }}</div>
            <div class="stats-subtext">Alla dagar i intervallet</div>
        </div>
    </div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Dag</th>
                    <th>V</th>
                    <th>Prognos</th>
                    @foreach($baselineYears as $year)
                        <th>{{ $year }}</th>
                        <th>Väder {{ $year }}</th>
                    @endforeach
                    <th>Väder {{ $comparisonYear }}</th>
                    <th>Bokade</th>
                    <th>Turer</th>
                    <th>Guider</th>
                    <th>Notering</th>
                </tr>
            </thead>
            <tbody>
                @forelse($series as $row)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $row['label'] }}</div>
                            <div class="small-muted">{{ $row['label_short'] }}</div>
                        </td>
                        <td>{{ $row['iso_week'] }}</td>
                        <td>{{ $row['forecast'] ?? '–' }}</td>
                        @foreach($baselineYears as $year)
                            <td>{{ $row['historical_by_year'][$year] ?? '–' }}</td>
                            <td class="small-muted">
                                @include('partials.admin.weather-summary', [
                                    'summary' => $row['weather_by_year'][$year] ?? null,
                                ])
                            </td>
                        @endforeach
                        <td class="small-muted">
                            @include('partials.admin.weather-summary', [
                                'summary' => $row['weather_current'] ?? null,
                            ])
                        </td>
                        <td>{{ $row['current'] }}</td>
                        <td>{{ $row['tours_count'] ?? 0 }}</td>
                        <td>{{ $row['guides_count'] ?? 0 }}</td>
                        <td class="small-muted">
                            @if(! empty($row['day_note']))
                                <span title="{{ $row['day_note'] }}">{{ \Illuminate\Support\Str::limit($row['day_note'], 60) }}</span>
                            @else
                                –
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 8 + (count($baselineYears) * 2) }}" class="text-center small-muted py-4">Ingen period vald.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(($showChart ?? false) && count($series ?? []) > 0)
<style>
    .historical-chart-shell {
        position: relative;
        width: 100%;
    }
    .historical-chart-canvas {
        position: relative;
        height: 320px;
        width: 100%;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        const chartPayload = @json($chartData);
        const tooltips = chartPayload.tooltips || [];
        const canvas = document.getElementById('historicalPeriodChart');
        if (!canvas || !chartPayload.datasets || chartPayload.datasets.length === 0) {
            return;
        }

        Chart.defaults.font.family = 'Figtree, system-ui, sans-serif';
        Chart.defaults.font.size = 12;
        Chart.defaults.color = '#475569';
        Chart.defaults.borderColor = 'rgba(148, 163, 184, 0.18)';

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: chartPayload.labels || [],
                datasets: chartPayload.datasets,
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            title: function (items) {
                                const index = items[0]?.dataIndex ?? 0;
                                return tooltips[index] || items[0]?.label || '';
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                        },
                    },
                },
            },
        });
    })();
</script>
@endif
@endsection
