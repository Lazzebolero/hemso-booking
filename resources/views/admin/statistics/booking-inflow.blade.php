@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
    $statisticsRoute = route(\App\Support\ActiveRole::routeName('statistics.index'));
    $inflowRoute = route(\App\Support\ActiveRole::routeName('statistics.booking-inflow'));
@endphp

<div class="stats-page">
    <div class="page-header stats-header">
        <div>
            <h2 class="page-title">Bokningsinflöde</h2>
            <div class="page-subtitle">
                När bokningarna skapas (skapad tid) — för bemanning och bedömning av turstarter.
                Översikt per timme; expandera för 5‑minutersintervall.
                {{ $tourTypeLabel }}.
            </div>
        </div>

        <div class="page-actions">
            <a href="{{ route(\App\Support\ActiveRole::routeName('statistics.ferry-booking-waves'), request()->query()) }}" class="btn btn-outline-secondary">
                <i class="bi bi-water me-2"></i>Färja & bokningsvågor
            </a>
            <a href="{{ $statisticsRoute }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Övrig statistik
            </a>
        </div>
    </div>

    <div class="page-card compact-card mb-4">
        @include('partials.admin.statistics-period-filter', [
            'formAction' => $inflowRoute,
            'formId' => 'booking-inflow-filter-form',
            'period' => $period,
            'date' => $date,
            'from' => $from,
            'to' => $to,
            'year' => $year,
            'month' => $month,
            'submitLabel' => 'Visa inflöde',
            'showTourTypeFilter' => true,
            'tourTypes' => $tourTypes,
            'tourTypeFilterValue' => $tourTypeFilterValue,
            'scheduleTourTypeLabel' => $tourTypeLabel,
        ])
    </div>

    <div class="stats-kpi-grid mb-4">
        <div class="stats-card premium-kpi">
            <div class="stats-label">Bokningar skapade</div>
            <div class="stats-value">{{ $inflow['summary']['bookings'] }}</div>
            <div class="stats-subtext">{{ $from->toDateString() }} – {{ $to->toDateString() }}</div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Personer i bokningarna</div>
            <div class="stats-value">{{ $inflow['summary']['people'] }}</div>
            <div class="stats-subtext">Exkl. avbokade och väntelista</div>
        </div>
    </div>

    <div class="page-card" x-data="{ openHour: null }">
        <div class="section-title mb-1">Inflöde per timme</div>
        <div class="page-subtitle mb-3">
            Timmar aggregerade över vald period. Klicka + för 5‑minutersdetalj inom timmen.
        </div>

        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th style="width: 48px;"></th>
                        <th style="width: 160px;">Tid</th>
                        <th style="width: 140px;">Bokningar</th>
                        <th style="width: 140px;">Personer</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inflow['hours'] as $hourRow)
                        <tr>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    @click="openHour = openHour === {{ $hourRow['hour'] }} ? null : {{ $hourRow['hour'] }}"
                                    :aria-expanded="openHour === {{ $hourRow['hour'] }} ? 'true' : 'false'"
                                    aria-label="Visa 5-minutersintervall"
                                >
                                    <i class="bi" :class="openHour === {{ $hourRow['hour'] }} ? 'bi-dash-lg' : 'bi-plus-lg'"></i>
                                </button>
                            </td>
                            <td class="fw-semibold">{{ $hourRow['label'] }}</td>
                            <td>{{ $hourRow['bookings'] }}</td>
                            <td>{{ $hourRow['people'] }}</td>
                        </tr>
                        <tr class="booking-inflow-slots" x-show="openHour === {{ $hourRow['hour'] }}" x-cloak>
                            <td></td>
                            <td colspan="3" class="py-2">
                                <table class="table-modern mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 160px;">Intervall</th>
                                            <th style="width: 140px;">Bokningar</th>
                                            <th style="width: 140px;">Personer</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($hourRow['slots'] as $slot)
                                            <tr>
                                                <td class="small-muted">{{ $slot['label'] }}</td>
                                                <td>{{ $slot['bookings'] }}</td>
                                                <td>{{ $slot['people'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center muted py-4">
                                Inga bokningar skapades under vald period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
