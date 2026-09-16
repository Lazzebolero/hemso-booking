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
                    <div class="small-muted">Planerade turer kvar idag som inte har startat ännu.</div>
                </div>
            </div>

            @include('partials.admin.dashboard-upcoming-tours-table', [
                'tours' => $upcomingToursTodayCollection,
                'prefix' => $prefix,
                'showTourDate' => false,
                'showStartButton' => true,
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
                    <div class="small-muted">Kompakt översikt över dagens schema.</div>
                </div>
            </div>

            <div class="table-responsive-modern">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th style="width: 115px;">Tid</th>
                            <th>Tur</th>
                            <th style="width: 135px;">Guide</th>
                            <th style="width: 85px;">Språk</th>
                            <th style="width: 90px;">Bokade</th>
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
                            @endphp

                            <tr>
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
                                    <div class="fw-semibold">{{ $tour->guide?->name ?? 'Ej tilldelad' }}</div>
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
                                <td colspan="7" class="text-center muted py-4">
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
                                • {{ $tour->guide?->name ?? 'Ej tilldelad' }}
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
                                    • {{ $tour->guide?->name ?? 'Ej tilldelad' }}
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
    </div>
</div>

<script>
    setTimeout(function () {
        window.location.reload();
    }, 30000);
</script>
@endsection