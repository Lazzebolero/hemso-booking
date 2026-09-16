@extends('layouts.app')

@section('content')
@php
    $statisticsRoute = route(\App\Support\ActiveRole::routeName('statistics.index'));
    $wavesRoute = route(\App\Support\ActiveRole::routeName('statistics.ferry-booking-waves'));
    $travelMinutes = $waves['travel_minutes'] ?? 20;
@endphp

<div class="stats-page">
    <div class="page-header stats-header">
        <div>
            <h2 class="page-title">Färja & bokningsvågor</h2>
            <div class="page-subtitle">
                Bokningar kopplas till närmast föregående avgång från Strinningen utifrån
                <strong>skapad tid</strong> minus {{ $travelMinutes }} min restid.
                Aggregerat över vald period · {{ $tourTypeLabel }}.
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
            'formAction' => $wavesRoute,
            'formId' => 'ferry-booking-waves-filter-form',
            'period' => $period,
            'date' => $date,
            'from' => $from,
            'to' => $to,
            'year' => $year,
            'month' => $month,
            'submitLabel' => 'Visa vågor',
            'showTourTypeFilter' => true,
            'tourTypes' => $tourTypes,
            'tourTypeFilterValue' => $tourTypeFilterValue,
            'scheduleTourTypeLabel' => $tourTypeLabel,
        ])
    </div>

    <div class="stats-kpi-grid mb-4">
        <div class="stats-card premium-kpi">
            <div class="stats-label">Bokningar i perioden</div>
            <div class="stats-value">{{ $waves['summary']['bookings'] }}</div>
            <div class="stats-subtext">{{ $from->toDateString() }} – {{ $to->toDateString() }}</div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Kopplade till färja</div>
            <div class="stats-value">{{ $waves['summary']['matched_bookings'] }}</div>
            <div class="stats-subtext">{{ $waves['summary']['matched_people'] }} personer</div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Ej kopplade</div>
            <div class="stats-value">{{ $waves['unmatched']['bookings'] }}</div>
            <div class="stats-subtext">{{ $waves['unmatched']['people'] }} personer</div>
        </div>
    </div>

    <div class="page-card">
        <div class="section-title mb-1">Per färjeavgång (Strinningen)</div>
        <div class="page-subtitle mb-3">
            Regel: senaste avgång ≤ skapad tid − {{ $travelMinutes }} min.
            Samma klockslag summeras över flera dagar i perioden.
            Bäst att tolka för walk-in / guidad visning.
        </div>

        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th style="width: 120px;">Avgång</th>
                        <th style="width: 140px;">Förväntad ankomst</th>
                        <th style="width: 140px;">Bokningar</th>
                        <th style="width: 140px;">Personer</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($waves['waves'] as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['departure_time'] }}</td>
                            <td class="small-muted">ca {{ $row['expected_arrival'] }}</td>
                            <td>{{ $row['bookings'] }}</td>
                            <td>{{ $row['people'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center muted py-4">
                                Inga bokningar att koppla under vald period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
