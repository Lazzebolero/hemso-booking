@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Dashboard</h2>
        <div class="muted">Pågående turer, dagens visningar och kommande turer.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route('quick-tours.create') }}" class="btn btn-primary">
            <i class="bi bi-lightning-charge-fill me-2"></i>Starta snabbtur
        </a>

        <a href="{{ route('admin.tours.create') }}" class="btn btn-outline-secondary">
            <i class="bi bi-plus-circle me-2"></i>Ny tur
        </a>

        <a href="{{ route('admin.bookings.create') }}" class="btn btn-outline-secondary">
            <i class="bi bi-journal-plus me-2"></i>Ny bokning
        </a>

        <a href="{{ route('admin.reports.create') }}" class="btn btn-outline-secondary">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>Ny felrapport
        </a>
    </div>
</div>

<div class="stats-grid mb-4">
    <div class="stats-card">
        <div class="stats-icon"><i class="bi bi-people-fill"></i></div>
        <div class="stats-label">Bokade besökare idag</div>
        <div class="stats-value">{{ $todayBookedPeople ?? 0 }}</div>
        <div class="stats-subtext">Totalt bokade personer på dagens turer</div>
    </div>

    <div class="stats-card accent-success">
        <div class="stats-icon"><i class="bi bi-play-circle-fill"></i></div>
        <div class="stats-label">På startad tur</div>
        <div class="stats-value">{{ $startedNotCompletedPeople ?? 0 }}</div>
        <div class="stats-subtext">
            Fördelat på {{ $startedToursCount ?? 0 }} {{ (($startedToursCount ?? 0) == 1) ? 'tur' : 'turer' }}
        </div>
    </div>

    <div class="stats-card accent-warning">
        <div class="stats-icon"><i class="bi bi-signpost-2-fill"></i></div>
        <div class="stats-label">Dagens visningar</div>
        <div class="stats-value">{{ isset($todayTours) ? $todayTours->count() : 0 }}</div>
        <div class="stats-subtext">Alla turer planerade idag</div>
    </div>

    <div class="stats-card accent-danger">
        <div class="stats-icon"><i class="bi bi-collection-fill"></i></div>
        <div class="stats-label">Totalt antal personer</div>
        <div class="stats-value">{{ $totalPeopleToday ?? 0 }}</div>
        <div class="stats-subtext">Summering av dagens bokade personer</div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="page-card">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <div class="section-title mb-1">Pågående turer</div>
                    <div class="muted">Turer som har startat men ännu inte avslutats.</div>
                </div>
            </div>

            @forelse($ongoingTours ?? [] as $tour)
                @php
                    $typeName = $tour->tourType?->name ?? '-';
                @endphp

                <div class="page-card mb-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                <div class="fw-bold">{{ $tour->title }}</div>
                                <span class="badge-soft badge-soft-success">Pågående</span>
                            </div>

                            <div class="muted mb-2">
                                {{ $tour->tour_date ? \Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d') : '-' }}
                                kl {{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}
                            </div>

                            <div class="small muted">Turtyp</div>
                            <div class="fw-semibold mb-2">{{ $typeName }}</div>

                            <div class="small muted">Guide</div>
                            <div class="fw-semibold">{{ $tour->guide?->name ?? 'Ej tilldelad' }}</div>
                        
						@php
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
</div></div>

                        <div class="text-end">
                            <div class="small muted">Bokade personer</div>
                            <div class="fw-bold fs-4">{{ $tour->booked_people_count ?? 0 }}</div>

                            <div class="small muted mt-2">Antal bokningar</div>
                            <div class="fw-semibold">{{ $tour->booking_groups_count ?? 0 }}</div>

                            <div class="small muted mt-2">Max deltagare</div>
                            <div class="fw-semibold">{{ $tour->max_participants ?? 0 }}</div>

                            @php
                                $occupancyPercent = ($tour->max_participants ?? 0) > 0
                                    ? round((($tour->booked_people_count ?? 0) / $tour->max_participants) * 100)
                                    : 0;
                            @endphp

                            <div class="small muted mt-2">Beläggning</div>
                            <div class="fw-semibold mb-2">{{ $occupancyPercent }}%</div>
                            <div class="progress-modern" style="width: 220px; max-width: 100%;">
                                <div style="width: {{ min(100, $occupancyPercent) }}%; background: var(--brand-success);"></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end flex-wrap gap-2 mt-3">
                        <a href="{{ route('admin.tours.show', $tour) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye me-1"></i>Visa
                        </a>

                        <form method="POST" action="{{ route('admin.tours.complete', $tour) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-stop-fill me-1"></i>Avsluta
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="text-center muted py-4">
                    Inga pågående turer just nu.
                </div>
            @endforelse
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="page-card h-100">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <div class="section-title mb-1">Dagens visningar</div>
                    <div class="muted">Visar dagens turer med bokade personer och antal bokningar.</div>
                </div>
            </div>

            @forelse($todayTours ?? [] as $tour)
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

                    $typeName = $tour->tourType?->name ?? '-';
                @endphp

                <div class="page-card mb-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                <div class="fw-bold">{{ $tour->title }}</div>
                                <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                            </div>

                            <div class="muted mb-2">
                                {{ $tour->tour_date ? \Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d') : '-' }}
                                kl {{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}
                                @if(!empty($tour->end_time))
                                    – {{ substr($tour->end_time, 0, 5) }}
                                @endif
                            </div>

                            <div class="small muted">Turtyp</div>
                            <div class="fw-semibold mb-2">{{ $typeName }}</div>

                            <div class="small muted">Guide</div>
                            <div class="fw-semibold">{{ $tour->guide?->name ?? 'Ej tilldelad' }}</div>
							@php
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
                        </div>

                        <div class="text-end">
                            <div class="small muted">Bokade personer</div>
                            <div class="fw-bold fs-4">{{ $tour->booked_people_count ?? 0 }}</div>

                            <div class="small muted mt-2">Antal bokningar</div>
                            <div class="fw-semibold">{{ $tour->booking_groups_count ?? 0 }}</div>

                            <div class="small muted mt-2">Max deltagare</div>
                            <div class="fw-semibold">{{ $tour->max_participants ?? 0 }}</div>

                            @php
                                $occupancyPercent = ($tour->max_participants ?? 0) > 0
                                    ? round((($tour->booked_people_count ?? 0) / $tour->max_participants) * 100)
                                    : 0;
                            @endphp

                            <div class="small muted mt-2">Beläggning</div>
                            <div class="fw-semibold mb-2">{{ $occupancyPercent }}%</div>
                            <div class="progress-modern" style="width: 220px; max-width: 100%;">
                                <div style="width: {{ min(100, $occupancyPercent) }}%; background: var(--brand-success);"></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end flex-wrap gap-2 mt-3">
                        <a href="{{ route('admin.tours.show', $tour) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye me-1"></i>Visa
                        </a>

                        @if(($tour->status ?? null) !== 'completed')
                            <a href="{{ route('admin.tours.edit', $tour) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil-square me-1"></i>Redigera
                            </a>
                        @endif

                        @if(($tour->status ?? null) === 'planned')
                            <form method="POST" action="{{ route('admin.tours.start', $tour) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="bi bi-play-fill me-1"></i>Starta
                                </button>
                            </form>
                        @endif

                        @if(($tour->status ?? null) === 'started')
                            <form method="POST" action="{{ route('admin.tours.complete', $tour) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-stop-fill me-1"></i>Avsluta
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center muted py-4">
                    Inga turer finns för idag.
                </div>
            @endforelse
        </div>
    </div>

    <div class="col-xl-4">
        <div class="page-card h-100">
            <div class="section-title mb-3">Kommande turer</div>

            @forelse($upcomingTours ?? [] as $tour)
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
                @endphp

                <div class="border rounded-4 p-3 mb-3 bg-white">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div class="fw-semibold">{{ $tour->title }}</div>
                        <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>

                    <div class="small muted mb-1">
                        {{ $tour->tour_date ? \Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d') : '-' }}
                        kl {{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}
                    </div>@php
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

                    <div class="small muted mb-1">Turtyp</div>
                    <div class="fw-semibold mb-2">{{ $tour->tourType?->name ?? '-' }}</div>

                    <div class="d-flex justify-content-between small">
                        <span class="muted">Bokade</span>
                        <span class="fw-semibold">{{ $tour->booked_people_count ?? 0 }}</span>
                    </div>

                    <div class="d-flex justify-content-between small mt-1">
                        <span class="muted">Bokningar</span>
                        <span class="fw-semibold">{{ $tour->booking_groups_count ?? 0 }}</span>
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('admin.tours.show', $tour) }}" class="btn btn-sm btn-outline-secondary w-100">
                            Visa tur
                        </a>
                    </div>
                </div>
            @empty
                <div class="muted">Inga kommande turer hittades.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection