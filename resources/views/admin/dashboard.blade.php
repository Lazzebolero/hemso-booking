@extends('layouts.app')

@section('content')
@once
    <style>
        .dashboard-facility-reports-callout {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem 1.25rem;
            padding: 1.2rem 1.35rem;
            border-radius: 14px;
            border: 2px solid #ea580c;
            border-left-width: 8px;
            border-left-color: #c2410c;
            background: linear-gradient(125deg, #fff7ed 0%, #ffedd5 38%, #fed7aa 100%);
            box-shadow:
                0 0 0 1px rgba(234, 88, 12, 0.12),
                0 12px 32px rgba(234, 88, 12, 0.18),
                0 4px 12px rgba(15, 23, 42, 0.06);
        }

        .dashboard-facility-reports-callout .callout-icon-wrap {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            flex: 1 1 220px;
            min-width: 0;
        }

        .dashboard-facility-reports-callout .callout-icon {
            flex-shrink: 0;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(194, 65, 12, 0.15);
            color: #9a3412;
            font-size: 1.35rem;
        }

        .dashboard-facility-reports-callout .callout-title {
            font-size: 1.08rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #7c2d12;
            margin: 0 0 0.35rem;
            line-height: 1.25;
        }

        .dashboard-facility-reports-callout .callout-body {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
            color: #9a3412;
            line-height: 1.45;
        }

        .dashboard-facility-reports-callout .btn {
            flex-shrink: 0;
            font-weight: 700;
        }

        .dashboard-opening-check-callout {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem 1.25rem;
            padding: 1.2rem 1.35rem;
            border-radius: 14px;
            border: 2px solid #ca8a04;
            border-left-width: 8px;
            border-left-color: #a16207;
            background: linear-gradient(125deg, #fefce8 0%, #fef9c3 38%, #fde68a 100%);
            box-shadow:
                0 0 0 1px rgba(202, 138, 4, 0.12),
                0 12px 32px rgba(202, 138, 4, 0.16),
                0 4px 12px rgba(15, 23, 42, 0.06);
        }

        .dashboard-opening-check-callout .callout-icon-wrap {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            flex: 1 1 220px;
            min-width: 0;
        }

        .dashboard-opening-check-callout .callout-icon {
            flex-shrink: 0;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(161, 98, 7, 0.15);
            color: #854d0e;
            font-size: 1.35rem;
        }

        .dashboard-opening-check-callout .callout-title {
            font-size: 1.08rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #713f12;
            margin: 0 0 0.35rem;
            line-height: 1.25;
        }

        .dashboard-opening-check-callout .callout-body {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
            color: #854d0e;
            line-height: 1.45;
        }

        .ferry-dashboard-summary {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .ferry-dashboard-block {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 0.85rem;
            background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%);
        }

        .ferry-dashboard-label {
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 800;
            color: #64748b;
            margin-bottom: 0.25rem;
        }

        .ferry-dashboard-value {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
        }
    </style>
@endonce
@php
    $todayToursCollection = $todayTours ?? collect();
    $upcomingToursTodayCollection = $upcomingToursToday ?? collect();
    $upcomingToursAheadCollection = $upcomingToursAhead ?? collect();
    $ongoingToursCollection = $ongoingTours ?? collect();
    $lateUnstartedToursCollection = $lateUnstartedTours ?? collect();
    $aheadDays = (int) ($aheadDays ?? 7);

    $nextUpcomingTour = $upcomingToursTodayCollection->first() ?? $upcomingToursAheadCollection->first();
    $timeToNextTour = '-';

    $prefix = \App\Support\ActiveRole::routePrefix();

    if ($nextUpcomingTour && $nextUpcomingTour->tour_date && $nextUpcomingTour->start_time) {
        $nextTourAt = \Carbon\Carbon::parse($nextUpcomingTour->tour_date)
            ->setTimeFromTimeString($nextUpcomingTour->start_time);

        $minutes = max(0, (int) round(now()->diffInMinutes($nextTourAt, false)));

        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $remainingMinutes = $minutes % 60;

            $timeToNextTour = $remainingMinutes > 0
                ? $hours . 'h ' . $remainingMinutes . ' min'
                : $hours . 'h';
        } else {
            $timeToNextTour = $minutes . ' min';
        }
    }
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Dashboard</h2>
        <div class="page-subtitle">Översikt över pågående, kommande och dagens turer.</div>
    </div>

    <div class="page-actions">
        @include('partials.admin.dashboard-page-actions', [
            'prefix' => $prefix,
        ])
    </div>
</div>

@include('partials.ui.flash-messages')

@if(($openingCheckTablesReady ?? false) && ! ($todayOpeningCheckCompleted ?? false) && \Illuminate\Support\Facades\Route::has($prefix . '.opening-checks.index'))
    <div class="dashboard-opening-check-callout mb-4" role="status">
        <div class="callout-icon-wrap">
            <div class="callout-icon" aria-hidden="true">
                <i class="bi bi-door-open"></i>
            </div>
            <div>
                <p class="callout-title">Öppningskontroll saknas</p>
                <p class="callout-body">
                    Dagens öppningskontroll är inte slutförd. Turer kan startas, men anläggningen ska inte öppnas för besökare förrän kontrollen är gjord.
                </p>
            </div>
        </div>
        <a href="{{ route($prefix . '.opening-checks.index') }}" class="btn btn-dark">
            <i class="bi bi-list-ul me-2"></i>Visa öppningskontroll
        </a>
    </div>
@endif

@if(($openOpeningDeviationCount ?? 0) > 0 && \Illuminate\Support\Facades\Route::has($prefix . '.opening-checks.index'))
    <div class="dashboard-facility-reports-callout mb-4" role="status">
        <div class="callout-icon-wrap">
            <div class="callout-icon" aria-hidden="true">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <p class="callout-title">Öppna avvikelser</p>
                <p class="callout-body">
                    @if(($openOpeningDeviationCount ?? 0) === 1)
                        Det finns <strong>1</strong> öppen avvikelse från öppningskontrollen.
                    @else
                        Det finns <strong>{{ $openOpeningDeviationCount }}</strong> öppna avvikelser från öppningskontrollen.
                    @endif
                </p>
            </div>
        </div>
        <a href="{{ route($prefix . '.opening-checks.index') }}" class="btn btn-dark">
            <i class="bi bi-list-ul me-2"></i>Visa avvikelser
        </a>
    </div>
@endif

@if(($newOpenFacilityReportsCount ?? 0) > 0)
    <div class="dashboard-facility-reports-callout mb-4" role="status">
        <div class="callout-icon-wrap">
            <div class="callout-icon" aria-hidden="true">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <p class="callout-title">Nya felrapporter</p>
                <p class="callout-body">
                    @if(($newOpenFacilityReportsCount ?? 0) === 1)
                        Det finns <strong>1</strong> öppen felrapport som inkommit sedan du senast öppnade listan.
                    @else
                        Det finns <strong>{{ $newOpenFacilityReportsCount }}</strong> öppna felrapporter som inkommit sedan du senast öppnade listan.
                    @endif
                </p>
            </div>
        </div>
        <a href="{{ route($prefix . '.reports.index') }}" class="btn btn-dark">
            <i class="bi bi-list-ul me-2"></i>Visa felrapporter
        </a>
    </div>
@endif

@if(($prefix ?? '') === 'admin')
    <div class="page-card compact-card mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="section-title mb-1">Externa produktioner</div>
                @if($currentProductions->isNotEmpty())
                    <div class="small-muted">
                        {{ $currentProductions->pluck('name')->implode(', ') }}
                        · {{ $productionInsideCount }} inne just nu
                        @if(($productionDepartedCount ?? 0) > 0)
                            ·
                            @if($currentProductions->count() === 1)
                                <a href="{{ route('admin.productions.show', $currentProductions->first()) }}">{{ $productionDepartedCount === 1 ? '1 deltagare märkt som åkt ut' : $productionDepartedCount.' deltagare märkta som åkt ut' }}</a>
                            @else
                                {{ $productionDepartedCount === 1 ? '1 deltagare märkt som åkt ut' : $productionDepartedCount.' deltagare märkta som åkt ut' }}
                            @endif
                        @endif
                    </div>
                @else
                    <div class="small-muted">Ingen aktiv TV-produktion just nu. Skapa ett projekt för att följa in/ut i berget.</div>
                @endif
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if($currentProductions->isNotEmpty())
                    <a href="{{ route('admin.productions.presence') }}" class="btn btn-outline-primary">
                        <i class="bi bi-broadcast me-2"></i>Närvaro i berget
                    </a>
                @endif
                <a href="{{ route('admin.productions.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-folder me-2"></i>
                    @if($currentProductions->isNotEmpty())
                        Projekt
                    @else
                        Skapa projekt
                    @endif
                </a>
            </div>
        </div>
    </div>
@endif

<div class="stats-grid mb-4">
    <div class="stats-card">
        <div class="stats-label">Bokade idag</div>
        <div class="stats-value">{{ $todayBookedPeople ?? 0 }}</div>
        <div class="stats-subtext">Totalt bokade personer på dagens turer</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">I berget just nu</div>
        <div class="stats-value">{{ $startedNotCompletedPeople ?? 0 }}</div>
        <div class="stats-subtext">
            Fördelade på {{ $startedToursCount ?? 0 }} {{ (($startedToursCount ?? 0) == 1) ? 'tur' : 'turer' }}
        </div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Turer idag</div>
        <div class="stats-value">{{ $todayToursCollection->count() }}</div>
        <div class="stats-subtext">Planerade, startade och avslutade</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Tid till nästa tur</div>
        <div class="stats-value">{{ $timeToNextTour }}</div>
        <div class="stats-subtext">
            @if($nextUpcomingTour)
                {{ !empty($nextUpcomingTour->start_time) ? substr($nextUpcomingTour->start_time, 0, 5) : '-' }}
                • {{ $nextUpcomingTour->title }}
            @else
                Ingen kommande tur
            @endif
        </div>
    </div>
</div>

<div class="admin-grid-2">
    <div>
        <div class="page-card compact-card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <div class="section-title mb-1">Kommande turer idag</div>
                    <div class="small-muted">
                        Planerade turer kvar idag som inte har startat ännu.
                        Väntetid (guidade visningar): kö = tidigaste tur med plats; förbokade = senare tur medvetet.
                        Varning över {{ $waitWarningMinutes ?? 45 }} min avser bara kö.
                    </div>
                </div>
            </div>

            @include('partials.admin.dashboard-upcoming-tours-table', [
                'tours' => $upcomingToursTodayCollection,
                'prefix' => $prefix,
                'showTourDate' => false,
                'showStartButton' => true,
                'showWaitTimes' => true,
                'waitWarningMinutes' => $waitWarningMinutes ?? 45,
                'emptyMessage' => 'Inga fler kommande turer idag.',
            ])
        </div>

        <div class="page-card compact-card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <div class="section-title mb-1">Kommande turer</div>
                    <div class="small-muted">
                        Imorgon och de närmaste {{ $aheadDays }} dagarna
                        @if(!empty($aheadEndDate))
                            (t.o.m. {{ \Carbon\Carbon::parse($aheadEndDate)->format('Y-m-d') }}).
                        @endif
                        Dagens turer visas ovan.
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a
                        href="{{ route($prefix . '.dashboard', ['ahead_days' => 7]) }}"
                        class="btn btn-sm {{ $aheadDays === 7 ? 'btn-primary' : 'btn-outline-secondary' }}"
                    >
                        7 dagar
                    </a>

                    <a
                        href="{{ route($prefix . '.dashboard', ['ahead_days' => 30]) }}"
                        class="btn btn-sm {{ $aheadDays === 30 ? 'btn-primary' : 'btn-outline-secondary' }}"
                    >
                        30 dagar
                    </a>
                </div>
            </div>

            @include('partials.admin.dashboard-upcoming-tours-table', [
                'tours' => $upcomingToursAheadCollection,
                'prefix' => $prefix,
                'showTourDate' => true,
                'emptyMessage' => 'Inga kommande turer de valda dagarna.',
            ])
        </div>

        <div class="page-card compact-card">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <div class="section-title mb-1">Dagens turer</div>
                    <div class="small-muted">
                        Kompakt översikt över dagens schema.
                        Väntetid (guidade visningar): kö vs förbokade (varning över {{ $waitWarningMinutes ?? 45 }} min avser bara kö).
                    </div>
                </div>
            </div>

            <style>
                tr.dashboard-tour-wait-warn td {
                    background: #fff7ed;
                }
            </style>

            <div class="table-responsive-modern">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th style="width: 115px;">Tid</th>
                            <th>Tur</th>
                            <th style="width: 135px;">Guide</th>
                            <th style="width: 85px;">Språk</th>
                            <th style="width: 90px;">Bokade</th>
                            <th style="width: 110px;">Väntetid</th>
                            <th style="width: 100px;">Status</th>
                            <th style="width: 280px;">Åtgärder</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($todayToursCollection as $tour)
                            @php
                                $status = $tour->status ?? 'planned';

                                $statusClass = match($status) {
                                    'planned' => 'badge-soft badge-soft-warning',
                                    'started' => 'badge-soft badge-soft-success',
                                    'completed' => 'badge-soft badge-soft-secondary',
                                    'cancelled' => 'badge-soft badge-soft-danger',
                                    default => 'badge-soft badge-soft-warning',
                                };

                                $statusLabel = match($status) {
                                    'planned' => 'Planerad',
                                    'started' => 'Startad',
                                    'completed' => 'Avslutad',
                                    'cancelled' => 'Inställd',
                                    default => ucfirst($status),
                                };

                                $languageCodes = $tour->bookings
                                    ->flatMap(fn ($booking) => $booking->languages->pluck('code'))
                                    ->filter()
                                    ->map(fn ($code) => strtoupper($code))
                                    ->unique()
                                    ->values();

                                $waitWarn = (bool) ($tour->wait_warn ?? false);
                            @endphp

                            <tr @class(['dashboard-tour-wait-warn' => $waitWarn])>
                                <td>
                                    <div class="fw-semibold">{{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}</div>
                                    <div class="small-muted">{{ $tour->tour_date ? \Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d') : '-' }}</div>
                                </td>

                                <td>
                                    <div class="d-flex flex-wrap align-items-center gap-1">
                                        <div class="fw-semibold">{{ $tour->title }}</div>
                                        @include('partials.tours.meal-badge', ['tour' => $tour])
                                    </div>
                                    <div class="small-muted">{{ $tour->tourType?->name ?? '-' }}</div>
                                </td>

                                <td>
                                    @include('partials.admin.tour-guide-cell', ['tour' => $tour])
                                </td>

                                <td>
                                    <div class="fw-semibold">
                                        @if($languageCodes->isEmpty())
                                            -
                                        @else
                                            {{ $languageCodes->implode(' + ') }}
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <div class="fw-bold">{{ $tour->booked_people_count ?? 0 }}</div>
                                    <div class="small-muted">av {{ $tour->max_participants ?? 0 }}</div>
                                </td>

                                <td>
                                    @include('partials.admin.tour-wait-time-cell', [
                                        'tour' => $tour,
                                        'waitWarningMinutes' => $waitWarningMinutes ?? 45,
                                    ])
                                </td>

                                <td>
                                    <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>

                                <td>
                                    <div class="toolbar-inline">
                                        @include('partials.admin.tour-start-form', [
                                            'tour' => $tour,
                                            'prefix' => $prefix,
                                        ])

                                        <a href="{{ route($prefix . '.tours.show', $tour) }}" class="btn btn-sm btn-outline-secondary">
                                            Visa
                                        </a>

                                        @if(($tour->status ?? null) !== 'completed')
                                            <a href="{{ route($prefix . '.tours.edit', $tour) }}" class="btn btn-sm btn-outline-secondary">
                                                Redigera
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center muted py-4">
                                    Inga turer finns för idag.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div>
        @php
            $ferryNext = $ferrySnapshot['next'] ?? null;
            $ferryLast = $ferrySnapshot['last'] ?? null;
        @endphp

        <div class="page-card compact-card mb-3">
            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-3">
                <div>
                    <div class="section-title mb-1">Hemsöleden · Strinningen</div>
                    <div class="small-muted">
                        Avgångar till ön enligt tidtabell
                        @if($ferrySnapshot['traffic_live'] ?? false)
                            · live från Trafikverket
                        @endif
                    </div>
                </div>

                @if(Route::has('ferry-schedule.index'))
                    <a href="{{ route('ferry-schedule.index') }}" class="btn btn-sm btn-outline-secondary">
                        Idag
                    </a>
                @endif
            </div>

            @include('partials.ferry.traffic-alerts', [
                'alerts' => $ferrySnapshot['traffic_alerts'] ?? [],
                'compact' => true,
            ])

            <div class="ferry-dashboard-summary">
                <div class="ferry-dashboard-block">
                    <div class="ferry-dashboard-label">Senast avgått</div>
                    <div class="ferry-dashboard-value">{{ $ferryLast['time'] ?? '-' }}</div>
                    @if($ferryLast['from_live_api'] ?? false)
                        <div class="small-muted">enligt Trafikverket</div>
                    @endif
                    @if($ferryLast['is_extra'] ?? false)
                        <div class="small-muted">Extratur</div>
                    @endif
                </div>

                <div class="ferry-dashboard-block">
                    <div class="ferry-dashboard-label">Nästa avgång</div>
                    <div class="ferry-dashboard-value">
                        @if($ferryNext && !($ferryNext['is_cancelled'] ?? false))
                            {{ $ferryNext['time'] }}
                            @if(($ferryNext['minutes_until'] ?? null) !== null)
                                <span class="small-muted">· om {{ $ferryNext['minutes_until'] }} min</span>
                            @endif
                            @if(($ferryNext['delay_minutes'] ?? null) > 0)
                                <span class="small-muted">· försenad</span>
                            @endif
                        @elseif($ferryNext['is_cancelled'] ?? false)
                            <span class="text-danger">Inställd</span>
                        @else
                            -
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="page-card compact-card">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div class="section-title mb-0">Pågående turer</div>
                <div class="small-muted">Uppdateras automatiskt var 30:e sekund</div>
            </div>

            @forelse($ongoingToursCollection as $tour)
                @php
                    $estimatedEndTime = '-';
                    $remainingToEnd = '-';

                    if (!empty($tour->started_at) && !empty($tour->start_time) && !empty($tour->end_time)) {
                        try {
                            $normalizedStart = strlen($tour->start_time) === 5 ? $tour->start_time . ':00' : $tour->start_time;
                            $normalizedEnd = strlen($tour->end_time) === 5 ? $tour->end_time . ':00' : $tour->end_time;

                            $plannedStart = \Carbon\Carbon::createFromFormat('H:i:s', $normalizedStart);
                            $plannedEnd = \Carbon\Carbon::createFromFormat('H:i:s', $normalizedEnd);

                            $durationMinutes = $plannedStart->diffInMinutes($plannedEnd, false);

                            if ($durationMinutes > 0) {
                                $actualEndAt = \Carbon\Carbon::parse($tour->started_at)->addMinutes($durationMinutes);
                                $estimatedEndTime = $actualEndAt->format('H:i');

                                $remainingMinutes = (int) now()->diffInMinutes($actualEndAt, false);

                                if ($remainingMinutes > 60) {
                                    $hours = floor($remainingMinutes / 60);
                                    $minutes = $remainingMinutes % 60;
                                    $remainingToEnd = $minutes > 0 ? $hours . 'h ' . $minutes . ' min kvar' : $hours . 'h kvar';
                                } elseif ($remainingMinutes > 0) {
                                    $remainingToEnd = $remainingMinutes . ' min kvar';
                                } elseif ($remainingMinutes === 0) {
                                    $remainingToEnd = 'slutar nu';
                                } else {
                                    $remainingToEnd = 'borde vara klar';
                                }
                            }
                        } catch (\Throwable $e) {
                        }
                    }
                @endphp

                <div class="info-item mb-3">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-1">
                                <div class="fw-semibold">{{ $tour->title }}</div>
                                @include('partials.tours.meal-badge', ['tour' => $tour])
                            </div>
                            <div class="small-muted">
                                @if(!empty($tour->started_at))
                                    Turen startade {{ \Carbon\Carbon::parse($tour->started_at)->format('H:i') }}
                                @elseif(!empty($tour->start_time))
                                    {{ substr($tour->start_time, 0, 5) }}
                                @else
                                    -
                                @endif
                                • @include('partials.admin.tour-guide-cell', ['tour' => $tour, 'variant' => 'inline'])
                            </div>
                        </div>
                        <span class="badge-soft badge-soft-success">Pågående</span>
                    </div>

                    <div class="d-flex justify-content-between small mt-2">
                        <span class="muted">Bokade</span>
                        <span class="fw-semibold">{{ $tour->booked_people_count ?? 0 }}</span>
                    </div>

                    <div class="d-flex justify-content-between small mt-2">
                        <span class="muted">Beräknas klar</span>
                        <span class="fw-semibold">{{ $estimatedEndTime }}</span>
                    </div>

                    <div class="d-flex justify-content-between small mt-1">
                        <span class="muted">Tid kvar</span>
                        <span class="fw-semibold">{{ $remainingToEnd }}</span>
                    </div>

                    <div class="toolbar-inline mt-3">
                        <a href="{{ route($prefix . '.tours.show', $tour) }}#tour-bookings" class="btn btn-sm btn-outline-secondary w-100">
                            Bokningar
                        </a>

                        <a href="{{ route($prefix . '.tours.show', $tour) }}" class="btn btn-sm btn-outline-secondary w-100">
                            Visa tur
                        </a>
                    </div>
                </div>
            @empty
                <div class="muted small">Inga pågående turer just nu.</div>
            @endforelse

            @if($lateUnstartedToursCollection->isNotEmpty())
                <hr style="border:0;border-top:1px solid #e2e8f0;margin:1rem 0;">

                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="section-title mb-0">Ej startade turer</div>
                    <div class="small-muted">Starttid passerad med mer än 10 minuter</div>
                </div>

                @foreach($lateUnstartedToursCollection as $tour)
                    <div class="info-item mb-3" style="background:#fff7ed;border-color:#fed7aa;">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="d-flex flex-wrap align-items-center gap-1">
                                    <div class="fw-semibold">{{ $tour->title }}</div>
                                    @include('partials.tours.meal-badge', ['tour' => $tour])
                                </div>
                                <div class="small-muted">
                                    {{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}
                                    • @include('partials.admin.tour-guide-cell', ['tour' => $tour, 'variant' => 'inline'])
                                </div>
                            </div>
                            <span class="badge-soft badge-soft-warning">Ej startad</span>
                        </div>

                        <div class="d-flex justify-content-between small mt-2">
                            <span class="muted">Bokade</span>
                            <span class="fw-semibold">{{ $tour->booked_people_count ?? 0 }}</span>
                        </div>

                        <div class="toolbar-inline mt-3">
                            @include('partials.admin.tour-start-form', [
                                'tour' => $tour,
                                'prefix' => $prefix,
                                'fullWidth' => true,
                            ])

                            <a href="{{ route($prefix . '.tours.show', $tour) }}" class="btn btn-sm btn-outline-secondary w-100">
                                Visa
                            </a>

                            <a href="{{ route($prefix . '.tours.edit', $tour) }}" class="btn btn-sm btn-outline-secondary w-100">
                                Redigera
                            </a>

                            @if(Route::has($prefix . '.tours.cancel'))
                                <form method="POST" action="{{ route($prefix . '.tours.cancel', $tour) }}" class="w-100" onsubmit="return confirm('Ställa in turen?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                        Ställ in
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <div class="page-card compact-card mt-3">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <div class="section-title mb-0">Dagens länder</div>
                    <div class="small-muted">Snabbnotering — oberoende av bokningar</div>
                </div>
                @php $prefix = \App\Support\ActiveRole::routePrefix(); @endphp
                @if(\Illuminate\Support\Facades\Route::has($prefix . '.daily-countries.edit'))
                    <a href="{{ route($prefix . '.daily-countries.edit') }}" class="btn btn-sm btn-outline-primary">
                        Redigera
                    </a>
                @endif
            </div>

            @forelse($todayLoggedCountries ?? [] as $row)
                <div class="d-flex align-items-center gap-2 mb-2">
                    <img src="{{ $row['flag_url'] }}" alt="" style="width:22px;height:16px;object-fit:cover;border-radius:2px;">
                    <span class="fw-semibold">{{ $row['name'] }}</span>
                </div>
            @empty
                <div class="muted small">Inga länder noterade idag.</div>
            @endforelse
        </div>

        <div class="page-card compact-card mt-3">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div class="section-title mb-0">Bokningar från andra länder idag</div>
                <div class="small-muted">{{ collect($todayForeignCountries ?? [])->sum('bookings') }} bokningar</div>
            </div>

            @forelse($todayForeignCountries ?? [] as $row)
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <img src="{{ $row['flag_url'] }}" alt="" style="width:22px;height:16px;object-fit:cover;border-radius:2px;">
                        <span class="fw-semibold">{{ $row['name'] }}</span>
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold">{{ $row['bookings'] }}</div>
                        <div class="small-muted">{{ $row['people'] }} pers</div>
                    </div>
                </div>
            @empty
                <div class="muted small">Inga bokningar från andra länder idag.</div>
            @endforelse
        </div>
    </div>
</div>

<script>
    setTimeout(function () {
        window.location.reload();
    }, 30000);
</script>
@endsection