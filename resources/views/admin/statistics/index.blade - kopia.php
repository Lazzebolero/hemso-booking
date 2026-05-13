@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Utökad</h2>
        <div class="muted">Välj dag, vecka, månad eller år och se översikt, diagram och turtypsanalys.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.statistics.export-csv', request()->query()) }}" class="btn btn-outline-secondary">
            <i class="bi bi-download me-2"></i>CSV-export
        </a>
    </div>
</div>

<div class="page-card mb-4">
    <form method="GET" action="{{ route('admin.statistics.index') }}" class="row g-3 align-items-end">
        <div class="col-md-4 col-lg-3">
            <label class="form-label">Period</label>
            <select name="period" class="form-select">
                <option value="day" @selected($period === 'day')>Dag</option>
                <option value="week" @selected($period === 'week')>Vecka</option>
                <option value="month" @selected($period === 'month')>Månad</option>
                <option value="year" @selected($period === 'year')>År</option>
            </select>
        </div>

        <div class="col-md-4 col-lg-3">
            <label class="form-label">Basdatum</label>
            <input type="date" name="date" class="form-control" value="{{ $date->toDateString() }}">
        </div>

        <div class="col-md-4 col-lg-3">
            <div class="small muted mb-2">Vald period</div>
            <div class="fw-semibold">{{ $from->toDateString() }} – {{ $to->toDateString() }}</div>
        </div>

        <div class="col-lg-3">
            <button class="btn btn-primary w-100">
                <i class="bi bi-funnel me-2"></i>Visa statistik
            </button>
        </div>
    </form>
</div>

<div class="stats-grid mb-4">
    <div class="stats-card">
        <div class="stats-icon"><i class="bi bi-people-fill"></i></div>
        <div class="stats-label">Bokade personer</div>
        <div class="stats-value">{{ $stats['summary']['booked_people'] ?? 0 }}</div>
        <div class="stats-subtext">Föregående period: {{ $comparison['summary']['booked_people'] ?? 0 }}</div>
    </div>

    <div class="stats-card accent-success">
        <div class="stats-icon"><i class="bi bi-journal-check"></i></div>
        <div class="stats-label">Bokningar</div>
        <div class="stats-value">{{ $stats['summary']['bookings'] ?? 0 }}</div>
        <div class="stats-subtext">Föregående period: {{ $comparison['summary']['bookings'] ?? 0 }}</div>
    </div>

    <div class="stats-card accent-warning">
        <div class="stats-icon"><i class="bi bi-bar-chart-fill"></i></div>
        <div class="stats-label">Beläggning</div>
        <div class="stats-value">{{ $stats['summary']['occupancy_rate'] ?? 0 }}%</div>
        <div class="stats-subtext">Väntelista: {{ $stats['summary']['waitlist'] ?? 0 }}</div>
    </div>

    <div class="stats-card accent-danger">
        <div class="stats-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div class="stats-label">No-show + sen avbokning</div>
        <div class="stats-value">{{ ($stats['summary']['no_show'] ?? 0) + ($stats['summary']['late_cancel'] ?? 0) }}</div>
        <div class="stats-subtext">
            No-show: {{ $stats['summary']['no_show'] ?? 0 }},
            sent: {{ $stats['summary']['late_cancel'] ?? 0 }}
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="page-card h-100">
            <div class="section-title mb-3">Utveckling över vald period</div>
            <div style="height: 340px;">
                <canvas id="timelineChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="page-card h-100">
            <div class="section-title mb-3">Fördelning</div>
            <div style="height: 340px;">
                <canvas id="distributionChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="page-card h-100">
            <div class="section-title mb-3">Periodsammanställning</div>
            <div class="row g-3">
                <div class="col-6">
                    <div class="small muted">Bokade</div>
                    <div class="fw-semibold">{{ $stats['day_summary']['booked'] ?? 0 }}</div>
                </div>

                <div class="col-6">
                    <div class="small muted">Avbokad sent</div>
                    <div class="fw-semibold">{{ $stats['day_summary']['late_cancel'] ?? 0 }}</div>
                </div>

                <div class="col-6">
                    <div class="small muted">Ej anlänt</div>
                    <div class="fw-semibold">{{ $stats['day_summary']['no_show'] ?? 0 }}</div>
                </div>

                <div class="col-6">
                    <div class="small muted">Avbokade</div>
                    <div class="fw-semibold">{{ $stats['day_summary']['cancelled'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="page-card h-100">
            <div class="section-title mb-3">Jämförelse med föregående period</div>

            @php
                $currentBooked = $stats['summary']['booked_people'] ?? 0;
                $previousBooked = $comparison['summary']['booked_people'] ?? 0;
                $change = $previousBooked > 0 ? round((($currentBooked - $previousBooked) / $previousBooked) * 100) : 0;
            @endphp

            <div class="small muted mb-2">Nuvarande period</div>
            <div class="fw-semibold mb-3">{{ $currentBooked }} bokade personer</div>

            <div class="small muted mb-2">Föregående period</div>
            <div class="fw-semibold mb-3">{{ $previousBooked }} bokade personer</div>

            <div class="small muted mb-2">Förändring</div>
            <div class="fw-semibold">{{ $change }}%</div>
        </div>
    </div>
</div>

<div class="page-card mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <div>
            <div class="section-title mb-1">Turtypsanalys</div>
            <div class="muted">Beläggning, snittstorlek, bokade personer, trend och jämförelse mellan turtyper.</div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stats-card">
            <div class="stats-icon"><i class="bi bi-trophy-fill"></i></div>
            <div class="stats-label">Mest bokad turtyp</div>
            <div class="stats-value" style="font-size: 1.25rem;">
                {{ $stats['tour_type_insights']['top_booked']['label'] ?? '-' }}
            </div>
            <div class="stats-subtext">
                {{ $stats['tour_type_insights']['top_booked']['booked_people'] ?? 0 }} bokade
            </div>
        </div>

        <div class="stats-card accent-success">
            <div class="stats-icon"><i class="bi bi-bar-chart-line-fill"></i></div>
            <div class="stats-label">Bästa beläggning</div>
            <div class="stats-value" style="font-size: 1.25rem;">
                {{ $stats['tour_type_insights']['best_occupancy']['label'] ?? '-' }}
            </div>
            <div class="stats-subtext">
                {{ $stats['tour_type_insights']['best_occupancy']['occupancy_percent'] ?? 0 }}%
            </div>
        </div>

        <div class="stats-card accent-warning">
            <div class="stats-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stats-label">Störst snittgrupp</div>
            <div class="stats-value" style="font-size: 1.25rem;">
                {{ $stats['tour_type_insights']['largest_average']['label'] ?? '-' }}
            </div>
            <div class="stats-subtext">
                {{ $stats['tour_type_insights']['largest_average']['avg_group_size'] ?? 0 }} personer/tur
            </div>
        </div>

        <div class="stats-card accent-danger">
            <div class="stats-icon"><i class="bi bi-signpost-split-fill"></i></div>
            <div class="stats-label">Antal turtyper</div>
            <div class="stats-value">{{ count($stats['tour_type_summary'] ?? []) }}</div>
            <div class="stats-subtext">Aktiva turtyper i vald period</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="page-card h-100">
            <div class="section-title mb-3">Bokade per turtyp</div>
            <div style="height: 340px;">
                <canvas id="tourTypeBookedChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="page-card h-100">
            <div class="section-title mb-3">Beläggning per turtyp</div>
            <div style="height: 340px;">
                <canvas id="tourTypeOccupancyChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="page-card h-100">
            <div class="section-title mb-3">Snittstorlek per turtyp</div>
            <div style="height: 340px;">
                <canvas id="tourTypeAverageChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="page-card h-100">
            <div class="section-title mb-3">Trend över tid per turtyp</div>
            <div style="height: 340px;">
                <canvas id="tourTypeTrendChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="page-card mb-4">
    <div class="section-title mb-3">Jämförelse mellan turtyper</div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Turtyp</th>
                    <th>Antal turer</th>
                    <th>Bokade personer</th>
                    <th>Snittstorlek</th>
                    <th>Beläggning</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stats['tour_type_comparison'] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['tours'] }}</td>
                        <td>{{ $row['booked_people'] }}</td>
                        <td>{{ $row['avg_group_size'] }}</td>
                        <td>{{ $row['occupancy_percent'] }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="muted">Ingen data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="page-card h-100">
            <div class="section-title mb-3">Beläggning per turtyp</div>

            @forelse($stats['occupancy_by_tour_type'] as $row)
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>{{ $row['label'] }}</span>
                        <span>{{ $row['occupancy_percent'] }}%</span>
                    </div>
                    <div class="progress-modern">
                        <div style="width: {{ min(100, $row['occupancy_percent']) }}%; background: var(--brand-success);"></div>
                    </div>
                </div>
            @empty
                <div class="muted">Ingen data.</div>
            @endforelse
        </div>
    </div>

    <div class="col-lg-4">
        <div class="page-card h-100">
            <div class="section-title mb-3">Beläggning per veckodag</div>

            @forelse($stats['occupancy_by_weekday'] as $row)
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>{{ $row['label'] }}</span>
                        <span>{{ $row['occupancy_percent'] }}%</span>
                    </div>
                    <div class="progress-modern">
                        <div style="width: {{ min(100, $row['occupancy_percent']) }}%; background: var(--brand-warning);"></div>
                    </div>
                </div>
            @empty
                <div class="muted">Ingen data.</div>
            @endforelse
        </div>
    </div>

    <div class="col-lg-4">
        <div class="page-card h-100">
            <div class="section-title mb-3">Beläggning per guide</div>

            @forelse($stats['occupancy_by_guide'] as $row)
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>{{ $row['label'] }}</span>
                        <span>{{ $row['occupancy_percent'] }}%</span>
                    </div>
                    <div class="progress-modern">
                        <div style="width: {{ min(100, $row['occupancy_percent']) }}%; background: var(--brand-danger);"></div>
                    </div>
                </div>
            @empty
                <div class="muted">Ingen data.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="page-card">
    <div class="section-title mb-3">Mest populära tider</div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Tid</th>
                    <th>Antal bokningar</th>
                    <th>Bokade personer</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stats['popular_times'] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['bookings'] }}</td>
                        <td>{{ $row['booked_people'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="muted">Ingen data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@php
    $timelineData = $stats['timeline'] ?? [
        'labels' => [],
        'booked' => [],
        'tours' => [],
    ];

    $distributionData = $stats['distribution'] ?? [];
    $tourTypeSummary = $stats['tour_type_summary'] ?? [];
    $tourTypeTimeline = $stats['tour_type_timeline'] ?? ['labels' => [], 'series' => []];
@endphp

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const timeline = @json($timelineData);
    const distribution = @json($distributionData);
    const tourTypeSummary = @json($tourTypeSummary);
    const tourTypeTimeline = @json($tourTypeTimeline);

    const tourTypeLabels = tourTypeSummary.map(item => item.label);
    const tourTypeBooked = tourTypeSummary.map(item => item.booked_people);
    const tourTypeOccupancy = tourTypeSummary.map(item => item.occupancy_percent);
    const tourTypeAverage = tourTypeSummary.map(item => item.avg_group_size);

    const timelineCanvas = document.getElementById('timelineChart');
    if (timelineCanvas) {
        new Chart(timelineCanvas, {
            type: 'line',
            data: {
                labels: timeline.labels || [],
                datasets: [
                    {
                        label: 'Bokade',
                        data: timeline.booked || [],
                        borderWidth: 2,
                        tension: 0.35
                    },
                    {
                        label: 'Turer',
                        data: timeline.tours || [],
                        borderWidth: 2,
                        tension: 0.35
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    const distributionCanvas = document.getElementById('distributionChart');
    if (distributionCanvas) {
        new Chart(distributionCanvas, {
            type: 'doughnut',
            data: {
                labels: Object.keys(distribution),
                datasets: [
                    {
                        data: Object.values(distribution),
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    const tourTypeBookedCanvas = document.getElementById('tourTypeBookedChart');
    if (tourTypeBookedCanvas) {
        new Chart(tourTypeBookedCanvas, {
            type: 'bar',
            data: {
                labels: tourTypeLabels,
                datasets: [
                    {
                        label: 'Bokade personer',
                        data: tourTypeBooked,
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    const tourTypeOccupancyCanvas = document.getElementById('tourTypeOccupancyChart');
    if (tourTypeOccupancyCanvas) {
        new Chart(tourTypeOccupancyCanvas, {
            type: 'bar',
            data: {
                labels: tourTypeLabels,
                datasets: [
                    {
                        label: 'Beläggning %',
                        data: tourTypeOccupancy,
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    const tourTypeAverageCanvas = document.getElementById('tourTypeAverageChart');
    if (tourTypeAverageCanvas) {
        new Chart(tourTypeAverageCanvas, {
            type: 'bar',
            data: {
                labels: tourTypeLabels,
                datasets: [
                    {
                        label: 'Snittstorlek',
                        data: tourTypeAverage,
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    const tourTypeTrendCanvas = document.getElementById('tourTypeTrendChart');
    if (tourTypeTrendCanvas) {
        new Chart(tourTypeTrendCanvas, {
            type: 'line',
            data: {
                labels: tourTypeTimeline.labels || [],
                datasets: (tourTypeTimeline.series || []).map(series => ({
                    label: series.label,
                    data: series.data,
                    borderWidth: 2,
                    tension: 0.35
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
</script>
@endsection