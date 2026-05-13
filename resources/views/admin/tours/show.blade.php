@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">{{ $tour->title }}</h2>
        <div class="muted">
            {{ $tour->tour_date ? \Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d') : '-' }}
        </div>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.tours.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>

        @if($tour->status !== 'completed')
            <a href="{{ route('admin.tours.edit', $tour) }}" class="btn btn-outline-secondary">
                <i class="bi bi-pencil me-2"></i>Redigera
            </a>
        @endif
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="page-card">
            <div class="section-title mb-3">Turinformation</div>@php
    $languageCodes = $tour->bookings
        ->flatMap(fn ($booking) => $booking->languages->pluck('code'))
        ->filter()
        ->map(fn ($code) => strtoupper($code))
        ->unique()
        ->values();
@endphp

<div class="small muted">Språk</div>
<div class="fw-semibold mb-2">
    @if($languageCodes->isEmpty())
        -
    @else
        {{ $languageCodes->implode(' + ') }}
    @endif
</div>

            <div class="row g-3">

                <div class="col-md-6">
                    <div class="small muted">Turtyp</div>
                    <div class="fw-semibold">
                        {{ $tour->tourType?->name ?? '-' }}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="small muted">Guide</div>
                    <div class="fw-semibold">
                        {{ $tour->guide?->name ?? 'Ej tilldelad' }}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="small muted">Planerad tid</div>
                    <div class="fw-semibold">
                        {{ $tour->start_time ? substr($tour->start_time, 0, 5) : '-' }}
                        @if($tour->end_time)
                            – {{ substr($tour->end_time, 0, 5) }}
                        @endif
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="small muted">Status</div>
                    <div class="fw-semibold">
                        @switch($tour->status)
                            @case('planned') Planerad @break
                            @case('started') Startad @break
                            @case('completed') Avslutad @break
                            @case('cancelled') Inställd @break
                            @default {{ $tour->status }}
                        @endswitch
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="small muted">Faktisk starttid</div>
                    <div class="fw-semibold">
                        {{ $tour->started_at ? \Carbon\Carbon::parse($tour->started_at)->format('H:i') : '-' }}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="small muted">Faktisk sluttid</div>
                    <div class="fw-semibold">
                        {{ $tour->ended_at ? \Carbon\Carbon::parse($tour->ended_at)->format('H:i') : '-' }}
                    </div>
                </div>

                @if($tour->started_at && $tour->ended_at)
                    @php
                        $startedAt = \Carbon\Carbon::parse($tour->started_at);
                        $endedAt = \Carbon\Carbon::parse($tour->ended_at);
                        $durationMinutes = $startedAt->diffInMinutes($endedAt);
                    @endphp

                    <div class="col-md-6">
                        <div class="small muted">Tid för turen</div>
                        <div class="fw-semibold">
                            {{ $durationMinutes }} minuter
                        </div>
                    </div>
                @endif

                @if($tour->description)
                    <div class="col-12">
                        <div class="small muted">Beskrivning</div>
                        <div>{{ $tour->description }}</div>
                    </div>
                @endif

            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="page-card h-100">
            <div class="section-title mb-3">Statistik</div>

            @php
                $activeBookings = $tour->bookings
                    ->whereNotIn('status', ['cancelled'])
                    ->where('is_waitlist', false);

                $totalPeople = $activeBookings->sum('total_count');
                $totalBookings = $activeBookings->count();

                $occupancy = ($tour->max_participants ?? 0) > 0
                    ? round(($totalPeople / $tour->max_participants) * 100)
                    : 0;
            @endphp

            <div class="mb-3">
                <div class="small muted">Bokade personer</div>
                <div class="fw-bold fs-4">{{ $totalPeople }}</div>
            </div>

            <div class="mb-3">
                <div class="small muted">Antal bokningar</div>
                <div class="fw-semibold">{{ $totalBookings }}</div>
            </div>

            <div class="mb-3">
                <div class="small muted">Max deltagare</div>
                <div class="fw-semibold">{{ $tour->max_participants }}</div>
            </div>

            <div class="mb-2">
                <div class="small muted">Beläggning</div>
                <div class="fw-semibold">{{ $occupancy }}%</div>
            </div>

            <div class="progress-modern">
                <div style="width: {{ min(100, $occupancy) }}%; background: var(--brand-success);"></div>
            </div>
        </div>
    </div>
</div>

<div class="page-card">
    <div class="section-title mb-3">Bokningar</div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Bokning</th>
                    <th>Kontakt</th>
                    <th>Antal</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tour->bookings as $booking)
                    <tr>
                        <td>{{ $booking->booking_name }}</td>
                        <td>{{ $booking->contact_name ?? '-' }}</td>
                        <td>{{ $booking->total_count }}</td>
                        <td>{{ $booking->status }}</td>
						<td>
                            <div class="btn-group">
                                <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn-sm btn-outline-secondary">
                                    Redigera
                                </a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center muted">Inga bokningar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection