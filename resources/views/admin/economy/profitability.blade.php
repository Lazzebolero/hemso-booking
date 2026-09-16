@extends('layouts.app')

@section('content')
@php
    $formatSek = fn (float $value): string => number_format($value, 2, ',', ' ').' kr';
    $formatHours = fn (float $value): string => number_format($value, 2, ',', ' ').' h';
@endphp

<style>
    .economy-profitability-table {
        width: 100%;
        min-width: 1180px;
        table-layout: fixed;
        border-collapse: collapse;
    }

    .economy-profitability-table col.col-date { width: 12%; }
    .economy-profitability-table col.col-num { width: 7%; }
    .economy-profitability-table col.col-money { width: 11%; }
    .economy-profitability-table col.col-hours { width: 9%; }

    .economy-profitability-table thead th,
    .economy-profitability-table tbody td,
    .economy-profitability-table tfoot td {
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

    .economy-profitability-table thead th {
        font-size: 0.78rem;
        font-weight: 700;
        color: #64748b;
        background: #f8fafc;
        border-bottom: 1px solid #dbe3ee;
    }

    .economy-profitability-table tfoot td {
        font-weight: 700;
        background: #f8fafc;
        border-bottom: 0;
        border-top: 1px solid #dbe3ee;
    }

    .economy-profitability-table .col-start {
        text-align: left !important;
    }

    .economy-profitability-table .col-end {
        text-align: right !important;
    }
</style>

<div class="stats-page">
    <div class="page-header stats-header">
        <div>
            <h2 class="page-title">Lönsamhet</h2>
            <div class="page-subtitle">
                Uppskattad intäkt utifrån analyspriser i Ekonomi.
                Ospecificerade räknas som vuxenpris. Kostnad = planerade guide-/värdtimmar × timkostnad
                + OB-timmar × OB-tillägg.
            </div>
        </div>

        <div class="page-actions">
            @if(Route::has('admin.restaurant-economy-cost.index'))
                <a href="{{ route('admin.restaurant-economy-cost.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-cup-hot me-2"></i>Restaurangkostnader
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
            'formAction' => route('admin.economy-profitability.index'),
            'formId' => 'economy-profitability-filter-form',
            'period' => $period,
            'date' => $date,
            'from' => $from,
            'to' => $to,
            'year' => $year,
            'month' => $month,
            'submitLabel' => 'Visa lönsamhet',
            'showTourTypeFilter' => false,
            'showChartToggle' => false,
        ])
    </div>

    <div class="stats-kpi-grid mb-4">
        <div class="stats-card premium-kpi">
            <div class="stats-label">Uppskattad intäkt</div>
            <div class="stats-value">{{ $formatSek($totals['revenue']) }}</div>
            <div class="stats-subtext">{{ $from->toDateString() }} – {{ $to->toDateString() }}</div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Personalkostnad</div>
            <div class="stats-value">{{ $formatSek($totals['cost']) }}</div>
            <div class="stats-subtext">
                {{ $formatHours($totals['staff_hours']) }}
                @if(($totals['ob_hours'] ?? 0) > 0)
                    · OB {{ $formatHours($totals['ob_hours']) }}
                @endif
                · {{ $totals['guide_count'] }} guider · {{ $totals['host_count'] }} värdar
            </div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Resultat</div>
            <div class="stats-value">{{ $formatSek($totals['result']) }}</div>
            <div class="stats-subtext">Intäkt minus personalkostnad</div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Deltagare</div>
            <div class="stats-value">{{ $totals['people'] }}</div>
            <div class="stats-subtext">Exkl. avbokade och väntelista</div>
        </div>
    </div>

    <div class="page-card mb-4">
        <div class="section-title mb-1">Per dag</div>
        <div class="page-subtitle mb-3">
            <strong>Vuxna</strong> = män + kvinnor.
            <strong>Ospec.</strong> räknas som vuxenpris i intäkten.
            <strong>Guider/Värdar</strong> = antal personer med planerade pass.
            Barn under 4 ingår i andelen gratis ({{ number_format($prices['economy_child_under4_percent'], 0, ',', ' ') }} %).
        </div>

        <div class="table-responsive-modern">
            <table class="economy-profitability-table">
                <colgroup>
                    <col class="col-date">
                    <col class="col-num">
                    <col class="col-num">
                    <col class="col-num">
                    <col class="col-num">
                    <col class="col-money">
                    <col class="col-num">
                    <col class="col-num">
                    <col class="col-hours">
                    <col class="col-money">
                    <col class="col-money">
                </colgroup>
                <thead>
                    <tr>
                        <th class="col-start" scope="col">Datum</th>
                        <th class="col-end" scope="col">Vuxna</th>
                        <th class="col-end" scope="col">Ospec.</th>
                        <th class="col-end" scope="col">Ungdom</th>
                        <th class="col-end" scope="col">Barn</th>
                        <th class="col-end" scope="col">Intäkt</th>
                        <th class="col-end" scope="col">Guider</th>
                        <th class="col-end" scope="col">Värdar</th>
                        <th class="col-end" scope="col">Timmar</th>
                        <th class="col-end" scope="col">Kostnad</th>
                        <th class="col-end" scope="col">Resultat</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($days as $day)
                        <tr>
                            <td class="col-start fw-semibold">{{ $day['label'] }}</td>
                            <td class="col-end">{{ $day['adult_count'] }}</td>
                            <td class="col-end">{{ $day['unspecified_count'] }}</td>
                            <td class="col-end">{{ $day['youth_count'] }}</td>
                            <td class="col-end">{{ $day['child_count'] }}</td>
                            <td class="col-end">{{ $formatSek($day['revenue']) }}</td>
                            <td class="col-end">{{ $day['guide_count'] }}</td>
                            <td class="col-end">{{ $day['host_count'] }}</td>
                            <td class="col-end">
                                {{ $formatHours($day['staff_hours']) }}
                                @if(($day['ob_hours'] ?? 0) > 0)
                                    <div class="small-muted">OB {{ $formatHours($day['ob_hours']) }}</div>
                                @endif
                            </td>
                            <td class="col-end">{{ $formatSek($day['cost']) }}</td>
                            <td class="col-end">{{ $formatSek($day['result']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td class="col-start">Summa</td>
                        <td class="col-end">{{ $totals['adult_count'] }}</td>
                        <td class="col-end">{{ $totals['unspecified_count'] }}</td>
                        <td class="col-end">{{ $totals['youth_count'] }}</td>
                        <td class="col-end">{{ $totals['child_count'] }}</td>
                        <td class="col-end">{{ $formatSek($totals['revenue']) }}</td>
                        <td class="col-end">{{ $totals['guide_count'] }}</td>
                        <td class="col-end">{{ $totals['host_count'] }}</td>
                        <td class="col-end">
                            {{ $formatHours($totals['staff_hours']) }}
                            @if(($totals['ob_hours'] ?? 0) > 0)
                                <div class="small-muted">OB {{ $formatHours($totals['ob_hours']) }}</div>
                            @endif
                        </td>
                        <td class="col-end">{{ $formatSek($totals['cost']) }}</td>
                        <td class="col-end">{{ $formatSek($totals['result']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
