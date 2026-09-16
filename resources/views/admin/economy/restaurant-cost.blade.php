@extends('layouts.app')

@section('content')
@php
    $formatSek = fn (float $value): string => number_format($value, 2, ',', ' ').' kr';
    $formatHours = fn (float $value): string => number_format($value, 2, ',', ' ').' h';
@endphp

<style>
    .economy-restaurant-cost-table {
        width: 100%;
        min-width: 860px;
        table-layout: fixed;
        border-collapse: collapse;
    }

    .economy-restaurant-cost-table col.col-date { width: 16%; }
    .economy-restaurant-cost-table col.col-num { width: 12%; }
    .economy-restaurant-cost-table col.col-money { width: 14%; }
    .economy-restaurant-cost-table col.col-hours { width: 14%; }

    .economy-restaurant-cost-table thead th,
    .economy-restaurant-cost-table tbody td,
    .economy-restaurant-cost-table tfoot td {
        padding: 0.7rem 0.55rem;
        vertical-align: middle;
        border-bottom: 1px solid #eef2f7;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        letter-spacing: normal;
        text-transform: none;
        box-sizing: border-box;
    }

    .economy-restaurant-cost-table thead th {
        font-size: 0.78rem;
        font-weight: 700;
        color: #64748b;
        background: #f8fafc;
        border-bottom: 1px solid #dbe3ee;
    }

    .economy-restaurant-cost-table tfoot td {
        font-weight: 700;
        background: #f8fafc;
        border-bottom: 0;
        border-top: 1px solid #dbe3ee;
    }

    .economy-restaurant-cost-table .col-start {
        text-align: left !important;
    }

    .economy-restaurant-cost-table .col-end {
        text-align: right !important;
    }
</style>

<div class="stats-page">
    <div class="page-header stats-header">
        <div>
            <h2 class="page-title">Restaurangkostnader</h2>
            <div class="page-subtitle">
                Personalkostnad för planerade restaurangpass. Kostnad = timmar × restaurangtimkostnad
                + OB-timmar × OB-tillägg. Matgäster visas som volym (inga intäkter beräknas).
            </div>
        </div>

        <div class="page-actions d-flex gap-2 flex-wrap">
            @if(Route::has('admin.economy-profitability.index'))
                <a href="{{ route('admin.economy-profitability.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-graph-up-arrow me-2"></i>Guidning – lönsamhet
                </a>
            @endif
            <a href="{{ route('admin.economy-settings.edit') }}" class="btn btn-outline-secondary">
                <i class="bi bi-cash-coin me-2"></i>Ekonomiinställningar
            </a>
            <a href="{{ route('admin.statistics.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Övrig statistik
            </a>
        </div>
    </div>

    <div class="page-card compact-card mb-4">
        @include('partials.admin.statistics-period-filter', [
            'formAction' => route('admin.restaurant-economy-cost.index'),
            'formId' => 'restaurant-economy-cost-filter-form',
            'period' => $period,
            'date' => $date,
            'from' => $from,
            'to' => $to,
            'year' => $year,
            'month' => $month,
            'submitLabel' => 'Visa kostnader',
            'showTourTypeFilter' => false,
            'showChartToggle' => false,
        ])
    </div>

    <div class="stats-kpi-grid mb-4">
        <div class="stats-card premium-kpi">
            <div class="stats-label">Personalkostnad</div>
            <div class="stats-value">{{ $formatSek($totals['cost']) }}</div>
            <div class="stats-subtext">{{ $from->toDateString() }} – {{ $to->toDateString() }}</div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Restaurangtimmar</div>
            <div class="stats-value">{{ $formatHours($totals['staff_hours']) }}</div>
            <div class="stats-subtext">
                @if(($totals['ob_hours'] ?? 0) > 0)
                    OB {{ $formatHours($totals['ob_hours']) }} ·
                @endif
                {{ $totals['staff_count'] }} personer
            </div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Matgäster</div>
            <div class="stats-value">{{ $totals['meal_people'] }}</div>
            <div class="stats-subtext">{{ $totals['meal_bookings'] }} bokningar med mat</div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Timkostnad restaurang</div>
            <div class="stats-value">{{ $formatSek($prices['economy_restaurant_hourly_cost']) }}</div>
            <div class="stats-subtext">OB {{ $formatSek($prices['economy_ob_hourly_amount']) }}/h</div>
        </div>
    </div>

    <div class="page-card mb-4">
        <div class="section-title mb-1">Per dag</div>
        <div class="page-subtitle mb-3">
            <strong>Matgäster</strong> = personer i aktiva bokningar markerade med mat.
            <strong>Personal</strong> = unika personer med planerade restaurangpass.
        </div>

        <div class="table-responsive-modern">
            <table class="economy-restaurant-cost-table">
                <colgroup>
                    <col class="col-date">
                    <col class="col-num">
                    <col class="col-num">
                    <col class="col-num">
                    <col class="col-hours">
                    <col class="col-money">
                </colgroup>
                <thead>
                    <tr>
                        <th class="col-start" scope="col">Datum</th>
                        <th class="col-end" scope="col">Matgäster</th>
                        <th class="col-end" scope="col">Matbokningar</th>
                        <th class="col-end" scope="col">Personal</th>
                        <th class="col-end" scope="col">Timmar</th>
                        <th class="col-end" scope="col">Kostnad</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($days as $day)
                        <tr>
                            <td class="col-start fw-semibold">{{ $day['label'] }}</td>
                            <td class="col-end">{{ $day['meal_people'] }}</td>
                            <td class="col-end">{{ $day['meal_bookings'] }}</td>
                            <td class="col-end">{{ $day['staff_count'] }}</td>
                            <td class="col-end">
                                {{ $formatHours($day['staff_hours']) }}
                                @if(($day['ob_hours'] ?? 0) > 0)
                                    <div class="small-muted">OB {{ $formatHours($day['ob_hours']) }}</div>
                                @endif
                            </td>
                            <td class="col-end">{{ $formatSek($day['cost']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td class="col-start">Summa</td>
                        <td class="col-end">{{ $totals['meal_people'] }}</td>
                        <td class="col-end">{{ $totals['meal_bookings'] }}</td>
                        <td class="col-end">{{ $totals['staff_count'] }}</td>
                        <td class="col-end">
                            {{ $formatHours($totals['staff_hours']) }}
                            @if(($totals['ob_hours'] ?? 0) > 0)
                                <div class="small-muted">OB {{ $formatHours($totals['ob_hours']) }}</div>
                            @endif
                        </td>
                        <td class="col-end">{{ $formatSek($totals['cost']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
