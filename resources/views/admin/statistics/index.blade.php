@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();

    $timelineData = $stats['timeline'] ?? [
        'labels' => [],
        'booked' => [],
        'tours' => [],
    ];

    $distributionData = $stats['distribution'] ?? [];
    $tourTypeSummary = $stats['tour_type_summary'] ?? [];
    $tourTypeTimeline = $stats['tour_type_timeline'] ?? ['labels' => [], 'series' => []];

    $currentBooked = $stats['summary']['booked_people'] ?? 0;
    $previousBooked = $comparison['summary']['booked_people'] ?? 0;
    $bookedChange = $previousBooked > 0 ? round((($currentBooked - $previousBooked) / $previousBooked) * 100) : 0;

    $currentBookings = $stats['summary']['bookings'] ?? 0;
    $previousBookings = $comparison['summary']['bookings'] ?? 0;
    $bookingsChange = $previousBookings > 0 ? round((($currentBookings - $previousBookings) / $previousBookings) * 100) : 0;

    $currentNoShowLate = ($stats['summary']['no_show'] ?? 0) + ($stats['summary']['late_cancel'] ?? 0);
    $previousNoShowLate = ($comparison['summary']['no_show'] ?? 0) + ($comparison['summary']['late_cancel'] ?? 0);
    $noShowLateChange = $previousNoShowLate > 0 ? round((($currentNoShowLate - $previousNoShowLate) / $previousNoShowLate) * 100) : 0;

    $menCount = $stats['summary']['men_count'] ?? 0;
    $womenCount = $stats['summary']['women_count'] ?? 0;
    $youthCount = $stats['summary']['youth_count'] ?? 0;
    $childCount = $stats['summary']['child_count'] ?? 0;
    $unspecifiedCount = $stats['summary']['unspecified_count'] ?? 0;

    $previousMenCount = $comparison['summary']['men_count'] ?? 0;
    $previousWomenCount = $comparison['summary']['women_count'] ?? 0;
    $previousYouthCount = $comparison['summary']['youth_count'] ?? 0;
    $previousChildCount = $comparison['summary']['child_count'] ?? 0;

    $familyBookingsCount = $stats['summary']['family_bookings_count'] ?? 0;
    $previousFamilyBookingsCount = $comparison['summary']['family_bookings_count'] ?? 0;
    $familyBookingsShare = $stats['summary']['family_bookings_share'] ?? 0;

    $nonSwedishLanguageBookingsCount = $stats['summary']['non_swedish_language_bookings_count'] ?? 0;
    $previousNonSwedishLanguageBookingsCount = $comparison['summary']['non_swedish_language_bookings_count'] ?? 0;
    $nonSwedishLanguageBookingsShare = $stats['summary']['non_swedish_language_bookings_share'] ?? 0;

    $foreignCountryBookingsCount = $stats['summary']['foreign_country_bookings_count'] ?? 0;
    $previousForeignCountryBookingsCount = $comparison['summary']['foreign_country_bookings_count'] ?? 0;
    $foreignCountryBookingsShare = $stats['summary']['foreign_country_bookings_share'] ?? 0;

    $groupTotal = $menCount + $womenCount + $youthCount + $childCount;

    $topBooked = $stats['tour_type_insights']['top_booked']['label'] ?? '-';
    $topBookedValue = $stats['tour_type_insights']['top_booked']['booked_people'] ?? 0;

    $largestAverage = $stats['tour_type_insights']['largest_average']['label'] ?? '-';
    $largestAverageValue = $stats['tour_type_insights']['largest_average']['avg_group_size'] ?? 0;

    $formatTrend = function ($value, $inverse = false) {
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

    $bookedTrend = $formatTrend($bookedChange);
    $bookingsTrend = $formatTrend($bookingsChange);
    $noShowTrend = $formatTrend($noShowLateChange, true);

    $menChange = $previousMenCount > 0 ? round((($menCount - $previousMenCount) / $previousMenCount) * 100) : ($menCount > 0 ? 100 : 0);
    $womenChange = $previousWomenCount > 0 ? round((($womenCount - $previousWomenCount) / $previousWomenCount) * 100) : ($womenCount > 0 ? 100 : 0);
    $youthChange = $previousYouthCount > 0 ? round((($youthCount - $previousYouthCount) / $previousYouthCount) * 100) : ($youthCount > 0 ? 100 : 0);
    $childChange = $previousChildCount > 0 ? round((($childCount - $previousChildCount) / $previousChildCount) * 100) : ($childCount > 0 ? 100 : 0);

    $menTrend = $formatTrend($menChange);
    $womenTrend = $formatTrend($womenChange);
    $youthTrend = $formatTrend($youthChange);
    $childTrend = $formatTrend($childChange);

    $familyChange = $previousFamilyBookingsCount > 0
        ? round((($familyBookingsCount - $previousFamilyBookingsCount) / $previousFamilyBookingsCount) * 100)
        : ($familyBookingsCount > 0 ? 100 : 0);
    $familyTrend = $formatTrend($familyChange);

    $nonSwedishLanguageChange = $previousNonSwedishLanguageBookingsCount > 0
        ? round((($nonSwedishLanguageBookingsCount - $previousNonSwedishLanguageBookingsCount) / $previousNonSwedishLanguageBookingsCount) * 100)
        : ($nonSwedishLanguageBookingsCount > 0 ? 100 : 0);
    $nonSwedishLanguageTrend = $formatTrend($nonSwedishLanguageChange);

    $foreignCountryChange = $previousForeignCountryBookingsCount > 0
        ? round((($foreignCountryBookingsCount - $previousForeignCountryBookingsCount) / $previousForeignCountryBookingsCount) * 100)
        : ($foreignCountryBookingsCount > 0 ? 100 : 0);
    $foreignCountryTrend = $formatTrend($foreignCountryChange);

    $statisticsRoute = route(\App\Support\ActiveRole::routeName('statistics.index'));
@endphp

<div class="stats-page">
    <div class="page-header stats-header">
        <div>
            <h2 class="page-title">Statistik</h2>
            <div class="page-subtitle">
                Analys av bokningar, turtyper, kön och åldersfördelning över vald period.
            </div>
        </div>

        <div class="page-actions d-flex gap-2 flex-wrap">
            @if(Route::has(\App\Support\ActiveRole::routePrefix() . '.statistics-notes.edit'))
                <a href="{{ route(\App\Support\ActiveRole::routePrefix() . '.statistics-notes.edit', ['date' => ($to ?? now())->toDateString()]) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-journal-text me-2"></i>Notera denna dag
                </a>
            @endif
            @if(Route::has(\App\Support\ActiveRole::routeName('statistics.booking-inflow')))
                <a href="{{ route(\App\Support\ActiveRole::routeName('statistics.booking-inflow'), request()->query()) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-clock me-2"></i>Bokningsinflöde
                </a>
            @endif
            @if(Route::has(\App\Support\ActiveRole::routeName('statistics.ferry-booking-waves')))
                <a href="{{ route(\App\Support\ActiveRole::routeName('statistics.ferry-booking-waves'), request()->query()) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-water me-2"></i>Färja & bokningsvågor
                </a>
            @endif
            <a href="{{ route('admin.statistics.historical-visitors.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-clock-history me-2"></i>Historisk besöksstatistik
            </a>
            <a href="{{ route('admin.statistics.export-csv', request()->query()) }}" class="btn btn-outline-secondary">
                <i class="bi bi-download me-2"></i>CSV-export
            </a>
        </div>
    </div>

    <div class="page-card compact-card mb-4">
        @include('partials.admin.statistics-period-filter', [
            'formAction' => $statisticsRoute,
            'formId' => 'statistics-filter-form',
            'period' => $period,
            'date' => $date,
            'from' => $from,
            'to' => $to,
            'year' => $year,
            'month' => $month,
            'submitLabel' => 'Visa statistik',
            'showTourTypeFilter' => true,
            'tourTypes' => $tourTypes,
            'tourTypeFilterValue' => $tourTypeFilterValue,
            'scheduleTourTypeLabel' => $tourTypeLabel,
        ])
    </div>

    <div class="stats-kpi-grid mb-4">
        <div class="stats-card premium-kpi">
            <div class="stats-label">Bokade personer</div>
            <div class="stats-value">{{ $currentBooked }}</div>
            <div class="stats-subtext">Föregående period: {{ $previousBooked }}</div>
            <div class="trend-row {{ $bookedTrend['class'] }}">
                <i class="bi {{ $bookedTrend['icon'] }}"></i>
                <span>{{ $bookedTrend['label'] }}</span>
            </div>
        </div>

        <div class="stats-card premium-kpi">
            <div class="stats-label">Bokningar</div>
            <div class="stats-value">{{ $currentBookings }}</div>
            <div class="stats-subtext">Föregående period: {{ $previousBookings }}</div>
            <div class="trend-row {{ $bookingsTrend['class'] }}">
                <i class="bi {{ $bookingsTrend['icon'] }}"></i>
                <span>{{ $bookingsTrend['label'] }}</span>
            </div>
        </div>

        <div class="stats-card premium-kpi">
            <div class="stats-label">No-show + sen avbokning</div>
            <div class="stats-value">{{ $currentNoShowLate }}</div>
            <div class="stats-subtext">
                No-show: {{ $stats['summary']['no_show'] ?? 0 }} • Sent: {{ $stats['summary']['late_cancel'] ?? 0 }}
            </div>
            <div class="trend-row {{ $noShowTrend['class'] }}">
                <i class="bi {{ $noShowTrend['icon'] }}"></i>
                <span>{{ $noShowTrend['label'] }}</span>
            </div>
        </div>

        <div class="stats-card premium-kpi">
            <div class="stats-label">Förändring mot föregående period</div>
            <div class="stats-value">{{ $bookedChange }}%</div>
            <div class="stats-subtext">
                Nuvarande: {{ $currentBooked }} • Föregående: {{ $previousBooked }}
            </div>
            <div class="trend-row {{ $bookedTrend['class'] }}">
                <i class="bi {{ $bookedTrend['icon'] }}"></i>
                <span>{{ $bookedTrend['label'] }}</span>
            </div>
        </div>
    </div>

    <div class="stats-kpi-grid mb-4">
        <div class="stats-card premium-kpi">
            <div class="stats-label">Män</div>
            <div class="stats-value">{{ $menCount }}</div>
            <div class="stats-subtext">Föregående period: {{ $previousMenCount }}</div>
            <div class="trend-row {{ $menTrend['class'] }}">
                <i class="bi {{ $menTrend['icon'] }}"></i>
                <span>{{ $menTrend['label'] }}</span>
            </div>
        </div>

        <div class="stats-card premium-kpi">
            <div class="stats-label">Kvinnor</div>
            <div class="stats-value">{{ $womenCount }}</div>
            <div class="stats-subtext">Föregående period: {{ $previousWomenCount }}</div>
            <div class="trend-row {{ $womenTrend['class'] }}">
                <i class="bi {{ $womenTrend['icon'] }}"></i>
                <span>{{ $womenTrend['label'] }}</span>
            </div>
        </div>

        <div class="stats-card premium-kpi">
            <div class="stats-label">Ungdomar</div>
            <div class="stats-value">{{ $youthCount }}</div>
            <div class="stats-subtext">Föregående period: {{ $previousYouthCount }}</div>
            <div class="trend-row {{ $youthTrend['class'] }}">
                <i class="bi {{ $youthTrend['icon'] }}"></i>
                <span>{{ $youthTrend['label'] }}</span>
            </div>
        </div>

        <div class="stats-card premium-kpi">
            <div class="stats-label">Barn</div>
            <div class="stats-value">{{ $childCount }}</div>
            <div class="stats-subtext">Föregående period: {{ $previousChildCount }}</div>
            <div class="trend-row {{ $childTrend['class'] }}">
                <i class="bi {{ $childTrend['icon'] }}"></i>
                <span>{{ $childTrend['label'] }}</span>
            </div>
        </div>

        <div class="stats-card premium-kpi">
            <div class="stats-label">Antal barnfamiljer</div>
            <div class="stats-value">{{ $familyBookingsCount }}</div>
            <div class="stats-subtext">
                Föregående period: {{ $previousFamilyBookingsCount }}
                • {{ $familyBookingsShare }}% av aktiva bokningar
            </div>
            <div class="trend-row {{ $familyTrend['class'] }}">
                <i class="bi {{ $familyTrend['icon'] }}"></i>
                <span>{{ $familyTrend['label'] }}</span>
            </div>
        </div>

        <div class="stats-card premium-kpi">
            <div class="stats-label">Bokningar med annat språk</div>
            <div class="stats-value">{{ $nonSwedishLanguageBookingsCount }}</div>
            <div class="stats-subtext">
                Föregående period: {{ $previousNonSwedishLanguageBookingsCount }}
                • {{ $nonSwedishLanguageBookingsShare }}% av aktiva bokningar
            </div>
            <div class="trend-row {{ $nonSwedishLanguageTrend['class'] }}">
                <i class="bi {{ $nonSwedishLanguageTrend['icon'] }}"></i>
                <span>{{ $nonSwedishLanguageTrend['label'] }}</span>
            </div>
        </div>

        <div class="stats-card premium-kpi">
            <div class="stats-label">Bokningar från andra länder</div>
            <div class="stats-value">{{ $foreignCountryBookingsCount }}</div>
            <div class="stats-subtext">
                Föregående period: {{ $previousForeignCountryBookingsCount }}
                • {{ $foreignCountryBookingsShare }}% av aktiva bokningar
            </div>
            <div class="trend-row {{ $foreignCountryTrend['class'] }}">
                <i class="bi {{ $foreignCountryTrend['icon'] }}"></i>
                <span>{{ $foreignCountryTrend['label'] }}</span>
            </div>
        </div>

        @if(Route::has($prefix . '.statistics.unspecified-follow-up'))
            <a href="{{ route($prefix . '.statistics.unspecified-follow-up') }}" class="text-decoration-none text-reset d-block">
                <div class="stats-card premium-kpi">
                    <div class="stats-label">Ospecificerade</div>
                    <div class="stats-value">{{ $unspecifiedCount }}</div>
                    <div class="stats-subtext">
                        @if(($stats['summary']['booked_people'] ?? 0) > 0)
                            {{ round(($unspecifiedCount / max(1, $stats['summary']['booked_people'])) * 100, 1) }}% av bokade
                        @else
                            Andel utan fördelning m/k/u/b
                        @endif
                        <span class="d-block mt-1"><i class="bi bi-arrow-right-short"></i>Visa uppföljning</span>
                    </div>
                </div>
            </a>
        @else
            <div class="stats-card premium-kpi">
                <div class="stats-label">Ospecificerade</div>
                <div class="stats-value">{{ $unspecifiedCount }}</div>
                <div class="stats-subtext">
                    @if(($stats['summary']['booked_people'] ?? 0) > 0)
                        {{ round(($unspecifiedCount / max(1, $stats['summary']['booked_people'])) * 100, 1) }}% av bokade
                    @else
                        Andel utan fördelning m/k/u/b
                    @endif
                </div>
            </div>
        @endif

        <div class="stats-card premium-kpi">
            <div class="stats-label">Totalt M/K/U/B</div>
            <div class="stats-value">{{ $groupTotal }}</div>
            <div class="stats-subtext">Summering av män, kvinnor, ungdomar och barn</div>
        </div>
    </div>

    <div class="stats-main-grid mb-4">
        <div class="page-card stats-main-chart">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="section-title mb-1">Utveckling över vald period</div>
                    <div class="small-muted">Bokade personer och antal turer per 15-minutersintervall.</div>
                </div>
            </div>

            <div class="chart-shell">
                <div class="stats-chart-lg">
                    <canvas id="timelineChart"></canvas>
                </div>
            </div>
        </div>

        <div class="stats-side-stack">
            <div class="page-card">
                <div class="section-title mb-3">Fördelning</div>
                <div class="chart-shell">
                    <div class="stats-chart-md">
                        <canvas id="distributionChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="page-card">
                <div class="section-title mb-3">Periodsammanställning</div>

                <div class="stats-mini-grid">
                    <div class="stats-mini-item">
                        <div class="small-muted">Bokade</div>
                        <div class="fw-semibold">{{ $stats['day_summary']['booked'] ?? 0 }}</div>
                    </div>

                    <div class="stats-mini-item">
                        <div class="small-muted">Avbokad sent</div>
                        <div class="fw-semibold">{{ $stats['day_summary']['late_cancel'] ?? 0 }}</div>
                    </div>

                    <div class="stats-mini-item">
                        <div class="small-muted">Ej anlänt</div>
                        <div class="fw-semibold">{{ $stats['day_summary']['no_show'] ?? 0 }}</div>
                    </div>

                    <div class="stats-mini-item">
                        <div class="small-muted">Avbokade</div>
                        <div class="fw-semibold">{{ $stats['day_summary']['cancelled'] ?? 0 }}</div>
                    </div>
                </div>
            </div>

            <div class="page-card">
                <div class="section-title mb-3">Snabbinsikter</div>

                <div class="stats-insight-list">
                    <div class="stats-insight-item">
                        <div class="small-muted">Mest bokad turtyp</div>
                        <div class="fw-semibold">{{ $topBooked }}</div>
                        <div class="small-muted">{{ $topBookedValue }} bokade</div>
                    </div>

                    <div class="stats-insight-item">
                        <div class="small-muted">Störst snittgrupp</div>
                        <div class="fw-semibold">{{ $largestAverage }}</div>
                        <div class="small-muted">{{ $largestAverageValue }} personer/tur</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="section-title mb-1">Turtypsanalys</div>
                <div class="small-muted">Jämförelse av bokningar, snittstorlek och trend.</div>
            </div>
        </div>

        <div class="stats-tourtype-kpis">
            <div class="stats-card compact-card">
                <div class="stats-label">Mest bokad turtyp</div>
                <div class="stats-value stats-value-sm">{{ $topBooked }}</div>
                <div class="stats-subtext">{{ $topBookedValue }} bokade</div>
            </div>

            <div class="stats-card compact-card">
                <div class="stats-label">Störst snittgrupp</div>
                <div class="stats-value stats-value-sm">{{ $largestAverage }}</div>
                <div class="stats-subtext">{{ $largestAverageValue }} personer/tur</div>
            </div>

            <div class="stats-card compact-card">
                <div class="stats-label">Antal turtyper</div>
                <div class="stats-value">{{ count($stats['tour_type_summary'] ?? []) }}</div>
                <div class="stats-subtext">Aktiva turtyper i perioden</div>
            </div>
        </div>
    </div>

    <div class="stats-chart-grid mb-4">
        <div class="page-card">
            <div class="section-title mb-3">Bokade per turtyp</div>
            <div class="chart-shell">
                <div class="stats-chart-md">
                    <canvas id="tourTypeBookedChart"></canvas>
                </div>
            </div>
        </div>

        <div class="page-card">
            <div class="section-title mb-3">Snittstorlek per turtyp</div>
            <div class="chart-shell">
                <div class="stats-chart-md">
                    <canvas id="tourTypeAverageChart"></canvas>
                </div>
            </div>
        </div>

        <div class="page-card">
            <div class="section-title mb-3">Trend per 15-minutersintervall och turtyp</div>
            <div class="chart-shell">
                <div class="stats-chart-md">
                    <canvas id="tourTypeTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="stats-bottom-grid">
        <div class="page-card">
            <div class="section-title mb-3">Jämförelse mellan turtyper</div>

            <div class="table-responsive-modern">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Turtyp</th>
                            <th style="width: 120px;">Antal turer</th>
                            <th style="width: 140px;">Bokade personer</th>
                            <th style="width: 120px;">Snittstorlek</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stats['tour_type_comparison'] as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['label'] }}</td>
                                <td>{{ $row['tours'] }}</td>
                                <td>{{ $row['booked_people'] }}</td>
                                <td>{{ $row['avg_group_size'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center muted py-4">Ingen data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="stats-side-stack">
            @php
                $maxBookedByTourType = collect($stats['tour_type_summary'] ?? [])->max('booked_people') ?: 1;
                $maxBookedByWeekday = collect($stats['booked_by_weekday'] ?? [])->max('booked') ?: 1;
                $maxBookedByGuide = collect($stats['booked_by_guide'] ?? [])->max('booked') ?: 1;
            @endphp

            <div class="page-card">
                <div class="section-title mb-3">Bokade per turtyp</div>

                @forelse($stats['tour_type_summary'] ?? [] as $row)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>{{ $row['label'] }}</span>
                            <span>{{ $row['booked_people'] }} pers</span>
                        </div>
                        <div class="progress-modern">
                            <div style="width: {{ min(100, round(($row['booked_people'] / $maxBookedByTourType) * 100)) }}%; background: var(--brand-success);"></div>
                        </div>
                    </div>
                @empty
                    <div class="muted">Ingen data.</div>
                @endforelse
            </div>

            <div class="page-card">
                <div class="section-title mb-1">Bokade per veckodag</div>
                <div class="page-subtitle mb-3">{{ $tourTypeLabel }}</div>

                @forelse($stats['booked_by_weekday'] ?? [] as $row)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>{{ $row['label'] }}</span>
                            <span>{{ $row['booked'] }} pers</span>
                        </div>
                        <div class="progress-modern">
                            <div style="width: {{ min(100, round(($row['booked'] / $maxBookedByWeekday) * 100)) }}%; background: var(--brand-warning);"></div>
                        </div>
                    </div>
                @empty
                    <div class="muted">Ingen data.</div>
                @endforelse
            </div>

            <div class="page-card">
                <div class="section-title mb-3">Bokade per guide</div>

                @forelse($stats['booked_by_guide'] ?? [] as $row)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>{{ $row['label'] }}</span>
                            <span>{{ $row['booked'] }} pers</span>
                        </div>
                        <div class="progress-modern">
                            <div style="width: {{ min(100, round(($row['booked'] / $maxBookedByGuide) * 100)) }}%; background: var(--brand-danger);"></div>
                        </div>
                    </div>
                @empty
                    <div class="muted">Ingen data.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="page-card mt-4">
        <div class="section-title mb-3">Fördelning män, kvinnor, ungdomar och barn</div>

        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th style="width: 140px;">Antal</th>
                        <th style="width: 160px;">Föregående period</th>
                        <th style="width: 140px;">Andel</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="fw-semibold">Män</td>
                        <td>{{ $menCount }}</td>
                        <td>{{ $previousMenCount }}</td>
                        <td>{{ $groupTotal > 0 ? round(($menCount / $groupTotal) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Kvinnor</td>
                        <td>{{ $womenCount }}</td>
                        <td>{{ $previousWomenCount }}</td>
                        <td>{{ $groupTotal > 0 ? round(($womenCount / $groupTotal) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Ungdomar</td>
                        <td>{{ $youthCount }}</td>
                        <td>{{ $previousYouthCount }}</td>
                        <td>{{ $groupTotal > 0 ? round(($youthCount / $groupTotal) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Barn</td>
                        <td>{{ $childCount }}</td>
                        <td>{{ $previousChildCount }}</td>
                        <td>{{ $groupTotal > 0 ? round(($childCount / $groupTotal) * 100, 1) : 0 }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @if(($period ?? '') !== 'year')
    <div class="page-card mt-4">
        <div class="section-title mb-1">Länder i perioden</div>
        <div class="page-subtitle mb-3">
            Bokningar med angivet land samt noterade dagar från Dagens länder (oberoende av enskilda bokningar). Sverige ingår inte.
        </div>

        @include('admin.statistics.partials.countries-breakdown-table', [
            'rows' => $stats['countries_breakdown'] ?? [],
            'emptyMessage' => 'Inga utländska länder i vald period.',
        ])
    </div>
    @endif

    <div class="page-card mt-4">
        <div class="section-title mb-1">Länder under året ({{ $year }})</div>
        <div class="page-subtitle mb-3">
            Alla utländska länder med besök under {{ $year }} — från bokningar och Dagens länder. Sverige ingår inte.
        </div>

        @include('admin.statistics.partials.countries-breakdown-table', [
            'rows' => $yearCountriesBreakdown ?? [],
            'emptyMessage' => 'Inga utländska länder under ' . $year . '.',
        ])
    </div>

    <div class="page-card mt-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <div class="section-title mb-1">Karta över årets länder ({{ $year }})</div>
                <div class="page-subtitle mb-0">
                    Choropleth-världskarta för utländska länder under {{ $year }}. Färgen rangordnar länder mot varandra (fler nyanser syns även när skillnaderna är stora).
                </div>
            </div>
            <a href="{{ route($prefix . '.statistics.year-countries-map', array_filter(['year' => $year, 'tour_type_id' => $tourTypeFilterValue !== 'all' ? $tourTypeFilterValue : null])) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrows-fullscreen me-1"></i>Öppna stor karta
            </a>
        </div>

        @if(! empty($yearCountriesBreakdown))
            <div id="year-countries-map" class="year-countries-map" aria-label="Karta över årets länder"></div>
            <div class="d-flex align-items-center gap-2 mt-3 small-muted">
                <span>Få</span>
                <span class="year-countries-map-legend-swatch year-countries-map-legend-low"></span>
                <span class="flex-grow-1 year-countries-map-legend-bar"></span>
                <span class="year-countries-map-legend-swatch year-countries-map-legend-high"></span>
                <span>Fler</span>
            </div>
        @else
            <div class="muted">Inga utländska länder att visa på kartan under {{ $year }}.</div>
        @endif
    </div>

    <div class="page-card mt-4">
        <div class="section-title mb-1">Väder & besök ({{ $year }})</div>
        <div class="page-subtitle mb-3">Dagar med besökare och väder från SMHI Lungö A · {{ $tourTypeLabel }}</div>

        <div class="stats-kpi-grid mb-0">
            <div class="stats-card premium-kpi">
                <div class="stats-label">Årets varmaste besöksdag</div>
                @if(($visitorWeatherExtremes['hottest'] ?? null) !== null)
                    @php $hottest = $visitorWeatherExtremes['hottest']; @endphp
                    <div class="stats-value">{{ rtrim(rtrim(number_format($hottest['temp_max'], 1, '.', ''), '0'), '.') }}°</div>
                    <div class="stats-subtext">
                        {{ $hottest['date_label'] }} · {{ ucfirst($hottest['weekday']) }} · {{ $hottest['booked'] }} bokade · dagens maxtemp
                        @if(! empty($hottest['weather_summary']))
                            <span class="d-block mt-1 small-muted">{{ $hottest['weather_summary'] }}</span>
                        @endif
                    </div>
                @else
                    <div class="stats-value">–</div>
                    <div class="stats-subtext">Ingen dag med både besökare och väderdata.</div>
                @endif
            </div>

            <div class="stats-card premium-kpi">
                <div class="stats-label">Årets kallaste besöksdag</div>
                @if(($visitorWeatherExtremes['coldest'] ?? null) !== null)
                    @php $coldest = $visitorWeatherExtremes['coldest']; @endphp
                    <div class="stats-value">{{ rtrim(rtrim(number_format($coldest['temp_max'], 1, '.', ''), '0'), '.') }}°</div>
                    <div class="stats-subtext">
                        {{ $coldest['date_label'] }} · {{ ucfirst($coldest['weekday']) }} · {{ $coldest['booked'] }} bokade · dagens maxtemp
                        @if(! empty($coldest['weather_summary']))
                            <span class="d-block mt-1 small-muted">{{ $coldest['weather_summary'] }}</span>
                        @endif
                    </div>
                @else
                    <div class="stats-value">–</div>
                    <div class="stats-subtext">Ingen dag med både besökare och väderdata.</div>
                @endif
            </div>

            <div class="stats-card premium-kpi">
                <div class="stats-label">Årets blötaste besöksdag</div>
                @if(($visitorWeatherExtremes['wettest'] ?? null) !== null)
                    @php $wettest = $visitorWeatherExtremes['wettest']; @endphp
                    <div class="stats-value">{{ rtrim(rtrim(number_format($wettest['precipitation_mm'], 1, '.', ''), '0'), '.') }} mm</div>
                    <div class="stats-subtext">
                        {{ $wettest['date_label'] }} · {{ ucfirst($wettest['weekday']) }} · {{ $wettest['booked'] }} bokade
                        @if(! empty($wettest['weather_summary']))
                            <span class="d-block mt-1 small-muted">{{ $wettest['weather_summary'] }}</span>
                        @endif
                    </div>
                @else
                    <div class="stats-value">–</div>
                    <div class="stats-subtext">Ingen besöksdag med nederbörd och väderdata.</div>
                @endif
            </div>

            <div class="stats-card premium-kpi">
                <div class="stats-label">Årets blåsigaste besöksdag</div>
                @if(($visitorWeatherExtremes['windiest'] ?? null) !== null)
                    @php $windiest = $visitorWeatherExtremes['windiest']; @endphp
                    <div class="stats-value">{{ rtrim(rtrim(number_format($windiest['wind_gust_max'], 1, '.', ''), '0'), '.') }} m/s</div>
                    <div class="stats-subtext">
                        {{ $windiest['date_label'] }} · {{ ucfirst($windiest['weekday']) }} · {{ $windiest['booked'] }} bokade · dagens max byvind
                        @if(! empty($windiest['weather_summary']))
                            <span class="d-block mt-1 small-muted">{{ $windiest['weather_summary'] }}</span>
                        @endif
                    </div>
                @else
                    <div class="stats-value">–</div>
                    <div class="stats-subtext">Ingen besöksdag med vinddata.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="page-card mt-4">
        <div class="section-title mb-1">Årets populäraste dagar ({{ $year }})</div>
        <div class="page-subtitle mb-3">
            Hela kalenderåret · {{ $tourTypeLabel }}.
            Turer = ej avbokade. Guider = unika huvudguider + medguider den dagen.
        </div>

        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th style="width: 180px;">Datum</th>
                        <th style="width: 140px;">Veckodag</th>
                        <th style="width: 150px;">Bokade personer</th>
                        <th style="width: 120px;">Turer</th>
                        <th style="width: 120px;">Guider</th>
                        <th>Väder (Lungö A)</th>
                        <th>Notering</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($popularDays ?? [] as $index => $row)
                        <tr>
                            <td class="fw-semibold">{{ $index + 1 }}</td>
                            <td>{{ $row['date_label'] }}</td>
                            <td>{{ ucfirst($row['weekday']) }}</td>
                            <td>{{ $row['booked'] }}</td>
                            <td>{{ $row['tours'] ?? 0 }}</td>
                            <td>{{ $row['guides'] ?? 0 }}</td>
                            <td class="small-muted">
                                @include('partials.admin.weather-summary', [
                                    'summary' => $row['weather_summary'] ?? null,
                                ])
                            </td>
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
                            <td colspan="8" class="text-center muted py-4">Ingen data för {{ $year }}.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="page-card mt-4">
        <div class="section-title mb-1">Mest populära tider</div>
        <div class="page-subtitle mb-3">{{ $tourTypeLabel }}</div>

        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th style="width: 140px;">Tid</th>
                        <th style="width: 150px;">Antal bokningar</th>
                        <th style="width: 150px;">Bokade personer</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stats['popular_times'] as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['label'] }}</td>
                            <td>{{ $row['bookings'] }}</td>
                            <td>{{ $row['booked_people'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center muted py-4">Ingen data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.stats-page {
    min-width: 1280px;
}
.stats-header {
    margin-bottom: 1.2rem;
}
.stats-kpi-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 1rem;
}
.premium-kpi {
    min-height: 146px;
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
.stats-main-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.8fr) minmax(360px, 0.9fr);
    gap: 1rem;
    align-items: start;
}
.stats-side-stack {
    display: grid;
    gap: 1rem;
}
.stats-main-chart {
    min-height: 100%;
}
.stats-chart-lg {
    height: 420px;
    position: relative;
}
.stats-chart-md {
    height: 300px;
    position: relative;
}
.year-countries-map {
    height: 420px;
    width: 100%;
    background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%);
    border: 1px solid var(--brand-line-soft);
    border-radius: 16px;
}
.year-countries-map .jvm-container {
    width: 100%;
    height: 100%;
}
.year-countries-map-legend-swatch {
    width: 14px;
    height: 14px;
    border-radius: 3px;
    border: 1px solid rgba(15, 23, 42, 0.12);
    flex-shrink: 0;
}
.year-countries-map-legend-low {
    background: #dbeafe;
}
.year-countries-map-legend-high {
    background: #1d4ed8;
}
.year-countries-map-legend-bar {
    height: 10px;
    border-radius: 999px;
    background: linear-gradient(90deg, #dbeafe 0%, #93c5fd 20%, #60a5fa 40%, #3b82f6 60%, #2563eb 80%, #1d4ed8 100%);
    border: 1px solid rgba(15, 23, 42, 0.08);
}
.chart-shell {
    background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%);
    border: 1px solid var(--brand-line-soft);
    border-radius: 16px;
    padding: 0.85rem 0.9rem 0.6rem;
}
.stats-mini-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}
.stats-mini-item {
    background: #f8fafc;
    border: 1px solid var(--brand-line-soft);
    border-radius: 12px;
    padding: 0.8rem 0.9rem;
}
.stats-insight-list {
    display: grid;
    gap: 0.75rem;
}
.stats-insight-item {
    background: #f8fafc;
    border: 1px solid var(--brand-line-soft);
    border-radius: 12px;
    padding: 0.85rem 0.95rem;
}
.stats-tourtype-kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
}
.stats-value-sm {
    font-size: 1.15rem;
    line-height: 1.2;
}
.stats-chart-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
.stats-bottom-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(340px, 0.9fr);
    gap: 1rem;
    align-items: start;
}
@media (max-width: 1400px) {
    .stats-kpi-grid,
    .stats-tourtype-kpis {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .stats-main-grid,
    .stats-bottom-grid,
    .stats-chart-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 900px) {
    .stats-page {
        min-width: 0;
    }

    .stats-kpi-grid,
    .stats-tourtype-kpis,
    .stats-mini-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap@1.6.0/dist/jsvectormap.min.css">
<script src="https://cdn.jsdelivr.net/npm/jsvectormap@1.6.0/dist/jsvectormap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsvectormap@1.6.0/dist/maps/world.js"></script>
@include('partials.admin.year-countries-choropleth')
<script>
    const timeline = @json($timelineData);
    const distribution = @json($distributionData);
    const tourTypeSummary = @json($tourTypeSummary);
    const tourTypeTimeline = @json($tourTypeTimeline);
    const yearCountriesMapData = @json($yearCountriesBreakdown ?? []);

    const tourTypeLabels = tourTypeSummary.map(item => item.label);
    const tourTypeBooked = tourTypeSummary.map(item => item.booked_people);
    const tourTypeAverage = tourTypeSummary.map(item => item.avg_group_size);

    const CHART_COLORS = {
        blue: '#2563eb',
        blueSoft: 'rgba(37, 99, 235, 0.16)',
        cyan: '#0891b2',
        cyanSoft: 'rgba(8, 145, 178, 0.16)',
        green: '#059669',
        greenSoft: 'rgba(5, 150, 105, 0.16)',
        amber: '#d97706',
        amberSoft: 'rgba(217, 119, 6, 0.18)',
        red: '#dc2626',
        redSoft: 'rgba(220, 38, 38, 0.16)',
        slate: '#64748b',
        slateSoft: 'rgba(100, 116, 139, 0.16)',
        violet: '#7c3aed',
        violetSoft: 'rgba(124, 58, 237, 0.16)'
    };

    Chart.defaults.font.family = 'Figtree, system-ui, sans-serif';
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#475569';
    Chart.defaults.borderColor = 'rgba(148, 163, 184, 0.18)';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
    Chart.defaults.plugins.legend.labels.boxWidth = 8;
    Chart.defaults.plugins.legend.labels.boxHeight = 8;
    Chart.defaults.plugins.legend.labels.padding = 16;

    const baseScales = {
        x: {
            grid: {
                display: false,
                drawBorder: false
            },
            ticks: {
                color: '#64748b',
                maxRotation: 0,
                autoSkip: true
            }
        },
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(148, 163, 184, 0.14)',
                drawBorder: false
            },
            ticks: {
                color: '#64748b',
                padding: 8
            }
        }
    };

    const basePlugins = {
        legend: {
            position: 'top',
            align: 'start'
        },
        tooltip: {
            backgroundColor: 'rgba(15, 23, 42, 0.94)',
            titleColor: '#ffffff',
            bodyColor: '#e2e8f0',
            borderColor: 'rgba(255,255,255,0.08)',
            borderWidth: 1,
            cornerRadius: 12,
            padding: 12,
            displayColors: true,
            boxPadding: 4
        }
    };

    const trendArrowPlugin = {
        id: 'trendArrowPlugin',
        afterDatasetsDraw(chart) {
            const options = chart?.config?.options?.plugins?.trendArrowPlugin;
            if (!options || !options.enabled) return;

            const datasetIndex = options.datasetIndex ?? 0;
            const meta = chart.getDatasetMeta(datasetIndex);
            const data = chart.data.datasets[datasetIndex]?.data || [];

            if (!meta || !meta.data || data.length < 2) return;

            const lastIndex = data.length - 1;
            const prevIndex = data.length - 2;

            const lastPoint = meta.data[lastIndex];
            const prevPoint = meta.data[prevIndex];

            if (!lastPoint || !prevPoint) return;

            const ctx = chart.ctx;
            const up = data[lastIndex] >= data[prevIndex];
            const color = up ? '#059669' : '#dc2626';

            const x = lastPoint.x;
            const y = lastPoint.y;
            const size = 10;

            ctx.save();
            ctx.fillStyle = color;
            ctx.beginPath();

            if (up) {
                ctx.moveTo(x, y - size);
                ctx.lineTo(x - size * 0.7, y);
                ctx.lineTo(x - size * 0.2, y);
                ctx.lineTo(x - size * 0.2, y + size);
                ctx.lineTo(x + size * 0.2, y + size);
                ctx.lineTo(x + size * 0.2, y);
                ctx.lineTo(x + size * 0.7, y);
            } else {
                ctx.moveTo(x, y + size);
                ctx.lineTo(x - size * 0.7, y);
                ctx.lineTo(x - size * 0.2, y);
                ctx.lineTo(x - size * 0.2, y - size);
                ctx.lineTo(x + size * 0.2, y - size);
                ctx.lineTo(x + size * 0.2, y);
                ctx.lineTo(x + size * 0.7, y);
            }

            ctx.closePath();
            ctx.fill();
            ctx.restore();
        }
    };

    const valueLabelsPlugin = {
        id: 'valueLabelsPlugin',
        afterDatasetsDraw(chart) {
            const options = chart?.config?.options?.plugins?.valueLabelsPlugin;
            if (!options || !options.enabled) return;

            const ctx = chart.ctx;
            const suffix = options.suffix ?? '';
            const color = options.color ?? '#334155';

            ctx.save();
            ctx.fillStyle = color;
            ctx.font = '700 11px Figtree, system-ui, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'bottom';

            chart.data.datasets.forEach((dataset, datasetIndex) => {
                const meta = chart.getDatasetMeta(datasetIndex);
                if (meta.hidden) return;

                meta.data.forEach((element, index) => {
                    const raw = dataset.data[index];
                    if (raw === null || raw === undefined || raw === '') return;

                    const value = typeof raw === 'number'
                        ? (Number.isInteger(raw) ? raw : raw.toFixed(1))
                        : raw;

                    ctx.fillText(String(value) + suffix, element.x, element.y - 6);
                });
            });

            ctx.restore();
        }
    };

    const doughnutCenterTextPlugin = {
        id: 'doughnutCenterTextPlugin',
        afterDraw(chart) {
            const options = chart?.config?.options?.plugins?.doughnutCenterTextPlugin;
            if (!options || !options.enabled) return;

            const meta = chart.getDatasetMeta(0);
            if (!meta || !meta.data || !meta.data.length) return;

            const dataset = chart.data.datasets[0];
            const values = dataset.data || [];
            const total = values.reduce((sum, value) => sum + (Number(value) || 0), 0);

            const centerX = meta.data[0].x;
            const centerY = meta.data[0].y;
            const ctx = chart.ctx;

            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            ctx.fillStyle = '#64748b';
            ctx.font = '700 11px Figtree, system-ui, sans-serif';
            ctx.fillText(options.label || 'Totalt', centerX, centerY - 12);

            ctx.fillStyle = '#0f172a';
            ctx.font = '800 22px Figtree, system-ui, sans-serif';
            ctx.fillText(String(total), centerX, centerY + 10);

            ctx.restore();
        }
    };

    Chart.register(trendArrowPlugin, valueLabelsPlugin, doughnutCenterTextPlugin);

    function createBarChart(canvasId, labels, data, label, color, softColor, suffix = '') {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: label,
                    data: data,
                    backgroundColor: softColor,
                    borderColor: color,
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
                        top: 24,
                        right: 8,
                        bottom: 0,
                        left: 4
                    }
                },
                plugins: {
                    ...basePlugins,
                    valueLabelsPlugin: {
                        enabled: true,
                        suffix: suffix,
                        color: '#334155'
                    }
                },
                scales: baseScales
            }
        });
    }

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
                        borderColor: CHART_COLORS.blue,
                        backgroundColor: CHART_COLORS.blueSoft,
                        fill: true,
                        tension: 0.38,
                        borderWidth: 3,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointBackgroundColor: CHART_COLORS.blue,
                        pointBorderWidth: 0
                    },
                    {
                        label: 'Turer',
                        data: timeline.tours || [],
                        borderColor: CHART_COLORS.cyan,
                        backgroundColor: CHART_COLORS.cyanSoft,
                        fill: true,
                        tension: 0.38,
                        borderWidth: 3,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointBackgroundColor: CHART_COLORS.cyan,
                        pointBorderWidth: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                layout: {
                    padding: {
                        top: 8,
                        right: 10,
                        bottom: 0,
                        left: 2
                    }
                },
                plugins: {
                    ...basePlugins,
                    trendArrowPlugin: {
                        enabled: true,
                        datasetIndex: 0
                    }
                },
                scales: baseScales
            }
        });
    }

    const distributionCanvas = document.getElementById('distributionChart');
    if (distributionCanvas) {
        new Chart(distributionCanvas, {
            type: 'doughnut',
            data: {
                labels: Object.keys(distribution),
                datasets: [{
                    data: Object.values(distribution),
                    backgroundColor: [
                        CHART_COLORS.blueSoft,
                        CHART_COLORS.greenSoft,
                        CHART_COLORS.amberSoft,
                        CHART_COLORS.redSoft,
                        CHART_COLORS.violetSoft,
                        CHART_COLORS.slateSoft
                    ],
                    borderColor: [
                        CHART_COLORS.blue,
                        CHART_COLORS.green,
                        CHART_COLORS.amber,
                        CHART_COLORS.red,
                        CHART_COLORS.violet,
                        CHART_COLORS.slate
                    ],
                    borderWidth: 1.5,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                layout: {
                    padding: {
                        top: 8,
                        right: 8,
                        bottom: 8,
                        left: 8
                    }
                },
                plugins: {
                    ...basePlugins,
                    doughnutCenterTextPlugin: {
                        enabled: true,
                        label: 'Totalt'
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 14
                        }
                    }
                }
            }
        });
    }

    createBarChart(
        'tourTypeBookedChart',
        tourTypeLabels,
        tourTypeBooked,
        'Bokade personer',
        CHART_COLORS.blue,
        CHART_COLORS.blueSoft
    );

    createBarChart(
        'tourTypeAverageChart',
        tourTypeLabels,
        tourTypeAverage,
        'Snittstorlek',
        CHART_COLORS.amber,
        CHART_COLORS.amberSoft
    );

    const tourTypeTrendCanvas = document.getElementById('tourTypeTrendChart');
    if (tourTypeTrendCanvas) {
        const palette = [
            { line: CHART_COLORS.blue, fill: CHART_COLORS.blueSoft },
            { line: CHART_COLORS.green, fill: CHART_COLORS.greenSoft },
            { line: CHART_COLORS.amber, fill: CHART_COLORS.amberSoft },
            { line: CHART_COLORS.red, fill: CHART_COLORS.redSoft },
            { line: CHART_COLORS.violet, fill: CHART_COLORS.violetSoft },
            { line: CHART_COLORS.cyan, fill: CHART_COLORS.cyanSoft },
            { line: CHART_COLORS.slate, fill: CHART_COLORS.slateSoft }
        ];

        new Chart(tourTypeTrendCanvas, {
            type: 'line',
            data: {
                labels: tourTypeTimeline.labels || [],
                datasets: (tourTypeTimeline.series || []).map((series, index) => {
                    const color = palette[index % palette.length];
                    return {
                        label: series.label,
                        data: series.data,
                        borderColor: color.line,
                        backgroundColor: color.fill,
                        borderWidth: 2.5,
                        tension: 0.35,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        fill: false
                    };
                })
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                layout: {
                    padding: {
                        top: 8,
                        right: 10,
                        bottom: 0,
                        left: 2
                    }
                },
                plugins: {
                    ...basePlugins,
                    trendArrowPlugin: {
                        enabled: true,
                        datasetIndex: 0
                    }
                },
                scales: baseScales
            }
        });
    }

    (function initYearCountriesMap() {
        const mapEl = document.getElementById('year-countries-map');
        if (!mapEl || typeof jsVectorMap === 'undefined' || !Array.isArray(yearCountriesMapData) || yearCountriesMapData.length === 0) {
            return;
        }

        const byCode = {};
        const rawValues = {};

        yearCountriesMapData.forEach((row) => {
            const code = String(row.code || '').toUpperCase();
            if (!code) {
                return;
            }

            const weight = Math.max(1, Number(row.bookings || 0) + Number(row.noted_days || 0));
            byCode[code] = row;
            rawValues[code] = weight;

            if (code === 'GB') {
                byCode.UK = row;
                rawValues.UK = weight;
            }
        });

        const choropleth = window.HemsoCountryChoropleth;
        const values = choropleth.toSeriesValues(rawValues);

        new jsVectorMap({
            selector: '#year-countries-map',
            map: 'world',
            backgroundColor: 'transparent',
            zoomOnScroll: false,
            regionStyle: {
                initial: {
                    fill: '#e2e8f0',
                    stroke: '#94a3b8',
                    strokeWidth: 0.4,
                },
                hover: {
                    fill: '#93c5fd',
                },
            },
            series: {
                regions: [
                    {
                        attribute: 'fill',
                        scale: choropleth.scale,
                        values: values,
                        normalizeFunction: choropleth.normalizeFunction,
                    },
                ],
            },
            onRegionTooltipShow(event, tooltip, code) {
                const row = byCode[code];
                if (!row) {
                    tooltip.text(code);
                    return;
                }

                tooltip.text(
                    row.name
                    + ' — '
                    + row.bookings + ' bokningar, '
                    + row.people + ' personer, '
                    + row.noted_days + ' noterade dagar'
                );
            },
        });
    })();
</script>
@endsection