@props([
    'tours',
    'showTourDate' => true,
    'emptyMessage' => 'Inga kommande turer hittades.',
    'useAppTable' => true,
])

<table @class(['restaurant-upcoming-table' => ! $useAppTable, 'restaurant-upcoming-table restaurant-upcoming-table--app' => $useAppTable])>
    <thead>
        <tr>
            <th style="width: {{ $showTourDate ? '95px' : '70px' }};">Tid</th>
            <th>Tur</th>
            <th style="width: 120px;">Guide</th>
            <th style="width: 65px;">Språk</th>
            <th style="width: 75px;">Bokade</th>
            <th style="width: 85px;">Beläggn.</th>
        </tr>
    </thead>
    <tbody>
        @forelse($tours as $tour)
            @php
                $booked = $tour->booked_people_count ?? 0;
                $max = $tour->max_participants ?? 0;
                $occupancyPercent = $max > 0 ? round(($booked / $max) * 100) : 0;

                $progressColor = $occupancyPercent < 40
                    ? '#dc2626'
                    : ($occupancyPercent < 70 ? '#d97706' : '#059669');

                $languageCodes = collect($tour->bookings ?? [])
                    ->flatMap(fn ($booking) => $booking->languages?->pluck('code') ?? collect())
                    ->filter()
                    ->map(fn ($code) => strtoupper($code))
                    ->unique()
                    ->values();

                $tourDateLabel = $tour->tour_date instanceof \DateTimeInterface
                    ? $tour->tour_date->format('Y-m-d')
                    : ($tour->tour_date ? (string) $tour->tour_date : '-');

                $startTimeLabel = ! empty($tour->start_time)
                    ? substr((string) $tour->start_time, 0, 5)
                    : '-';
            @endphp

            <tr>
                <td>
                    @if($showTourDate)
                        <div class="fw-semibold">{{ $tourDateLabel }}</div>
                        <div @class(['small-muted' => $useAppTable, 'muted' => ! $useAppTable])>{{ $startTimeLabel }}</div>
                    @else
                        <div class="fw-semibold">{{ $startTimeLabel }}</div>
                    @endif
                </td>

                <td>
                    <div @class(['d-flex flex-wrap align-items-center gap-1' => $useAppTable])>
                        <div class="fw-semibold">{{ $tour->title }}</div>
                        @include('partials.tours.meal-badge', ['tour' => $tour])
                    </div>
                    <div @class(['small-muted' => $useAppTable, 'muted' => ! $useAppTable])>{{ $tour->tourType?->name ?? '-' }}</div>
                </td>

                <td>
                    @include('partials.admin.tour-guide-display-block', [
                        'tour' => $tour,
                        'metaClass' => $useAppTable ? 'small-muted' : 'muted',
                    ])
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
                    <div class="restaurant-occupancy-bar">
                        <div style="width: {{ min(100, $occupancyPercent) }}%; background: {{ $progressColor }};"></div>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" @class(['text-center muted py-3' => $useAppTable, 'muted py-3' => ! $useAppTable])>
                    {{ $emptyMessage }}
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
