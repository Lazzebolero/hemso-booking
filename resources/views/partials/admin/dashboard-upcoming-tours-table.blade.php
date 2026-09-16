@props([
    'tours',
    'prefix',
    'showTourDate' => true,
    'showStartButton' => false,
    'showWaitTimes' => false,
    'waitWarningMinutes' => 45,
    'emptyMessage' => 'Inga kommande turer hittades.',
])

@php
    $columnCount = 7 + ($showWaitTimes ? 1 : 0);
@endphp

<style>
    tr.dashboard-tour-wait-warn td {
        background: #fff7ed;
    }
</style>

<div class="table-responsive-modern">
    <table class="table-modern">
        <thead>
            <tr>
                <th style="width: 130px;">Tid</th>
                <th>Tur</th>
                <th style="width: 140px;">Guide</th>
                <th style="width: 70px;">Språk</th>
                <th style="width: 80px;">Bokade</th>
                <th style="width: 90px;">Beläggning</th>
                @if($showWaitTimes)
                    <th style="width: 110px;">Väntetid</th>
                @endif
                <th style="width: {{ $showStartButton ? '320px' : '260px' }};">Åtgärder</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tours as $tour)
                @php
                    $booked = $tour->booked_people_count ?? 0;
                    $max = $tour->max_participants ?? 0;
                    $occupancyPercent = $max > 0 ? round(($booked / $max) * 100) : 0;

                    $progressColor = $occupancyPercent < 40
                        ? 'var(--brand-danger)'
                        : ($occupancyPercent < 70 ? 'var(--brand-warning)' : 'var(--brand-success)');

                    $languageCodes = $tour->bookings
                        ->flatMap(fn ($booking) => $booking->languages->pluck('code'))
                        ->filter()
                        ->map(fn ($code) => strtoupper($code))
                        ->unique()
                        ->values();

                    $waitWarn = (bool) ($tour->wait_warn ?? false);
                @endphp

                <tr @class(['dashboard-tour-wait-warn' => $showWaitTimes && $waitWarn])>
                    <td>
                        @if($showTourDate)
                            <div class="fw-semibold">
                                {{ $tour->tour_date ? \Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d') : '-' }}
                            </div>
                            <div class="small-muted">
                                {{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}
                            </div>
                        @else
                            <div class="fw-semibold">
                                {{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}
                            </div>
                        @endif
                    </td>

                    <td>
                        <div class="d-flex flex-wrap align-items-center gap-1">
                            <div class="fw-semibold">{{ $tour->title }}</div>
                            @include('partials.tours.meal-badge', ['tour' => $tour])
                            @include('partials.tours.ferry-badge', ['tour' => $tour])
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
                        <div class="fw-bold">{{ $booked }}/{{ $max }}</div>
                    </td>

                    <td>
                        <div class="fw-semibold mb-1">{{ $occupancyPercent }}%</div>
                        <div class="progress-modern" style="width: 86px;">
                            <div style="width: {{ min(100, $occupancyPercent) }}%; background: {{ $progressColor }};"></div>
                        </div>
                    </td>

                    @if($showWaitTimes)
                        <td>
                            @include('partials.admin.tour-wait-time-cell', [
                                'tour' => $tour,
                                'waitWarningMinutes' => $waitWarningMinutes,
                            ])
                        </td>
                    @endif

                    <td>
                        <div class="toolbar-inline">
                            @if($showStartButton)
                                @include('partials.admin.tour-start-form', [
                                    'tour' => $tour,
                                    'prefix' => $prefix,
                                ])
                            @endif

                            <a href="{{ route($prefix . '.bookings.create', ['tour_id' => $tour->id]) }}" class="btn btn-sm btn-primary">
                                Boka
                            </a>

                            <a href="{{ route($prefix . '.tours.show', $tour) }}" class="btn btn-sm btn-outline-secondary">
                                Visa
                            </a>

                            @include('partials.admin.tour-close-bookings-form', [
                                'tour' => $tour,
                                'prefix' => $prefix,
                                'buttonClass' => 'btn btn-sm btn-outline-warning',
                                'reopenButtonClass' => 'btn btn-sm btn-outline-secondary',
                            ])

                            <a href="{{ route($prefix . '.tours.edit', $tour) }}" class="btn btn-sm btn-outline-secondary">
                                Redigera
                            </a>

                            @if(Route::has($prefix . '.tours.cancel') && ($tour->status ?? null) === 'planned')
                                <form method="POST" action="{{ route($prefix . '.tours.cancel', $tour) }}" onsubmit="return confirm('Ställa in turen?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Ställ in
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $columnCount }}" class="text-center muted py-4">
                        {{ $emptyMessage }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
