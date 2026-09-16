@props([
    'tours',
    'useAppTable' => true,
])

@php
    $tableClass = $useAppTable ? 'table-modern' : '';
@endphp

<div @class(['table-responsive-modern' => $useAppTable])>
    <table class="{{ $tableClass }}">
        <thead>
            <tr>
                <th style="width: 115px;">Tid</th>
                <th>Tur</th>
                <th style="width: 135px;">Guide</th>
                <th style="width: 85px;">Språk</th>
                <th style="width: 90px;">Bokade</th>
                <th style="width: 100px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tours as $tour)
                @php
                    $status = $tour->status ?? 'planned';

                    $statusClass = match ($status) {
                        'planned' => 'badge-soft badge-soft-warning',
                        'started' => 'badge-soft badge-soft-success',
                        'completed' => 'badge-soft badge-soft-secondary',
                        'cancelled' => 'badge-soft badge-soft-danger',
                        default => 'badge-soft badge-soft-warning',
                    };

                    $statusLabel = match ($status) {
                        'planned' => 'Planerad',
                        'started' => 'Startad',
                        'completed' => 'Avslutad',
                        'cancelled' => 'Inställd',
                        default => ucfirst($status),
                    };

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
                        <div class="fw-semibold">{{ $startTimeLabel }}</div>
                        <div @class(['small-muted' => $useAppTable, 'muted' => ! $useAppTable])>
                            {{ $tourDateLabel }}
                        </div>
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
                        <div class="fw-bold">{{ $tour->booked_people_count ?? 0 }}</div>
                        <div @class(['small-muted' => $useAppTable, 'muted' => ! $useAppTable])>av {{ $tour->max_participants ?? 0 }}</div>
                    </td>

                    <td>
                        <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" @class(['text-center muted py-4' => $useAppTable, 'muted' => ! $useAppTable])>
                        Inga turer finns för idag.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
