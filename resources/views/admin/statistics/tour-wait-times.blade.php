@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
    $reportRoute = route(\App\Support\ActiveRole::routeName('statistics.tour-wait-times'));
    $statisticsRoute = route(\App\Support\ActiveRole::routeName('statistics.index'));
    $warningMinutes = $report['warning_minutes'] ?? 45;
    $summary = $report['summary'] ?? [];
@endphp

<div class="stats-page">
    <div class="page-header stats-header">
        <div>
            <h2 class="page-title">Långa väntetider</h2>
            <div class="page-subtitle">
                Guidade turer där max <strong>köväntan</strong> överstiger {{ $warningMinutes }} minuter
                (samma beräkning som dashboard/arkiv). Vald senare tur räknas inte.
                Period · {{ $tourTypeLabel }}.
            </div>
        </div>

        <div class="page-actions d-flex gap-2 flex-wrap">
            <a href="{{ route(\App\Support\ActiveRole::routeName('statistics.booking-inflow'), request()->query()) }}" class="btn btn-outline-secondary">
                <i class="bi bi-clock me-2"></i>Bokningsinflöde
            </a>
            <a href="{{ $statisticsRoute }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Övrig statistik
            </a>
        </div>
    </div>

    <div class="page-card compact-card mb-4">
        @include('partials.admin.statistics-period-filter', [
            'formAction' => $reportRoute,
            'formId' => 'tour-wait-times-filter-form',
            'period' => $period,
            'date' => $date,
            'from' => $from,
            'to' => $to,
            'year' => $year,
            'month' => $month,
            'submitLabel' => 'Visa rapport',
            'showTourTypeFilter' => true,
            'tourTypes' => $tourTypes,
            'tourTypeFilterValue' => $tourTypeFilterValue,
            'scheduleTourTypeLabel' => $tourTypeLabel,
        ])
    </div>

    <div class="stats-kpi-grid mb-4">
        <div class="stats-card premium-kpi">
            <div class="stats-label">Turer över {{ $warningMinutes }} min</div>
            <div class="stats-value">{{ $summary['over_threshold_tours'] ?? 0 }}</div>
            <div class="stats-subtext">{{ $from->toDateString() }} – {{ $to->toDateString() }}</div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Turer med ködata</div>
            <div class="stats-value">{{ $summary['tours_with_queue_samples'] ?? 0 }}</div>
            <div class="stats-subtext">
                @if(($summary['tours_with_queue_samples'] ?? 0) > 0)
                    {{ round((($summary['over_threshold_tours'] ?? 0) / $summary['tours_with_queue_samples']) * 100) }} % över tröskel
                @else
                    Inga köprover
                @endif
            </div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Köbokningar på flaggade turer</div>
            <div class="stats-value">{{ $summary['queue_bookings'] ?? 0 }}</div>
            <div class="stats-subtext">Samma-dagsbokningar i kö</div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Högsta max-väntan</div>
            <div class="stats-value">
                @if(($summary['max_wait_minutes'] ?? null) !== null)
                    {{ $summary['max_wait_minutes'] }} m
                @else
                    —
                @endif
            </div>
            <div class="stats-subtext">
                @if(($summary['avg_max_wait_minutes'] ?? null) !== null)
                    Snitt max {{ $summary['avg_max_wait_minutes'] }} m bland flaggade
                @else
                    Ingen data
                @endif
            </div>
        </div>
    </div>

    <div class="page-card">
        <div class="section-title mb-1">Turer över tröskel</div>
        <div class="page-subtitle mb-3">
            Köväntan = minuter från max(bokningstid, dagens första guidade slot) till turstart,
            när tidigare tur var full. Tröskel styrs i Inställningar (standard 45 min).
        </div>

        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Tid</th>
                        <th>Tur</th>
                        <th>Guide</th>
                        <th class="text-end">Max kö</th>
                        <th class="text-end">Snitt kö</th>
                        <th class="text-end">Köbokningar</th>
                        <th class="text-end">Vald (max)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['tours'] as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['date'] }}</td>
                            <td>{{ $row['start_time'] }}</td>
                            <td>
                                @if(Route::has($prefix . '.tours.show'))
                                    <a href="{{ route($prefix . '.tours.show', $row['id']) }}" class="text-decoration-none">
                                        <div class="fw-semibold">{{ $row['title'] ?: '—' }}</div>
                                        <div class="small-muted">{{ $row['tour_type'] ?? '' }}</div>
                                    </a>
                                @else
                                    <div class="fw-semibold">{{ $row['title'] ?: '—' }}</div>
                                    <div class="small-muted">{{ $row['tour_type'] ?? '' }}</div>
                                @endif
                            </td>
                            <td>{{ $row['guide_name'] ?? '—' }}</td>
                            <td class="text-end fw-semibold">{{ $row['wait_max_minutes'] ?? '—' }} m</td>
                            <td class="text-end">
                                {{ $row['wait_avg_minutes'] !== null ? $row['wait_avg_minutes'].' m' : '—' }}
                            </td>
                            <td class="text-end">{{ $row['wait_samples'] }}</td>
                            <td class="text-end small-muted">
                                @if(($row['wait_chosen_samples'] ?? 0) > 0)
                                    {{ $row['wait_chosen_max_minutes'] }} m ({{ $row['wait_chosen_samples'] }})
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center muted py-4">
                                Inga guidade turer med köväntan över {{ $warningMinutes }} minuter under vald period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
