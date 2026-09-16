@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="page-header">
    <div>
        <div class="page-title">Turer</div>
        <div class="page-subtitle">Hantera planerade, startade, avslutade och arkiverade turer.</div>
    </div>

    <div class="page-actions">
        @if(Route::has($prefix . '.tours.batch-create'))
            <a href="{{ route($prefix . '.tours.batch-create') }}" class="btn btn-outline-secondary">
                <i class="bi bi-calendar-plus me-2"></i>Skapa dagens turer
            </a>
        @endif

        <a href="{{ url($prefix . '/daily-guide-orders') }}" class="btn btn-outline-secondary">
            <i class="bi bi-sort-numeric-down me-2"></i>Dagens guider
        </a>

        <a href="{{ route($prefix . '.tours.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Ny tur
        </a>
    </div>
</div>

<div class="page-card card-compact mb-3">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
        <div>
            <div class="section-title mb-1">Välj vilka turer som ska visas</div>
            <div class="small-muted">
                Kommande turer är arbetslistan. Genomförda och inställda turer finns i arkivet.
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-start">
            <a
                href="{{ route($prefix . '.tours.index', array_merge(request()->except('page', 'scope'), ['scope' => 'upcoming'])) }}"
                class="btn btn-sm {{ ($scope ?? 'upcoming') === 'upcoming' ? 'btn-primary' : 'btn-outline-secondary' }}"
            >
                Visa aktiva och kommande turer
            </a>

            <a
                href="{{ route($prefix . '.tours.index', array_merge(request()->except('page', 'scope'), ['scope' => 'archive'])) }}"
                class="btn btn-sm {{ ($scope ?? 'upcoming') === 'archive' ? 'btn-primary' : 'btn-outline-secondary' }}"
            >
                Visa genomförda turer / arkiv
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route($prefix . '.tours.index') }}" class="row g-2 align-items-end">
        <input type="hidden" name="scope" value="{{ $scope ?? 'upcoming' }}">

        <div class="col-md-4">
            <label class="form-label small muted filter-label">Sök</label>
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                class="form-control form-control-sm"
                placeholder="Titel, guide eller turtyp"
            >
        </div>

        <div class="col-md-3">
            <label class="form-label small muted filter-label">Datum</label>
            <input
                type="date"
                name="date"
                value="{{ request('date') }}"
                class="form-control form-control-sm"
            >
        </div>

        <div class="col-md-3">
            <label class="form-label small muted filter-label">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Alla</option>
                @foreach(['planned' => 'Planerad', 'started' => 'Startad', 'completed' => 'Avslutad', 'cancelled' => 'Inställd'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-sm btn-primary w-100" type="submit">Filtrera</button>

            @if(request()->hasAny(['q', 'date', 'status']) || (($scope ?? 'upcoming') !== 'upcoming'))
                <a
                    href="{{ route($prefix . '.tours.index', ['scope' => $scope ?? 'upcoming']) }}"
                    class="btn btn-sm btn-outline-secondary w-100"
                >
                    Rensa
                </a>
            @endif
        </div>
    </form>
</div>

@php
    $showWaitTimes = ($scope ?? 'upcoming') === 'archive';
    $columnCount = 8 + ($showWaitTimes ? 1 : 0);
@endphp

<div class="page-card card-compact">
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
        <div>
            <div class="section-title mb-0">
                {{ $showWaitTimes ? 'Genomförda turer / arkiv' : 'Aktiva och kommande turer' }}
            </div>
            @if($showWaitTimes)
                <div class="small-muted">
                    Köväntan = samma-dagsbokningar på guidade visningar som tog tidigaste tur med plats.
                    Förbokade = senare tur trots att tidigare hade plats (t.ex. restaurang före).
                    Bussgrupper m.m. ingår inte. Varning över {{ $waitWarningMinutes ?? 45 }} min avser bara kö.
                </div>
            @endif
        </div>
        <div class="small muted">
            {{ method_exists($tours, 'total') ? $tours->total() : count($tours) }} turer
        </div>
    </div>

    @if($showWaitTimes)
        <style>
            tr.dashboard-tour-wait-warn td {
                background: #fff7ed;
            }
        </style>
    @endif

    <div class="table-responsive-modern">
        <table class="table-modern dashboard-table">
            <thead>
                <tr>
                    <th style="width: 130px;">Tid</th>
                    <th>Tur</th>
                    <th style="width: 95px;">Mat</th>
                    <th style="width: 150px;">Guide</th>
                    <th style="width: 80px;">Språk</th>
                    <th style="width: 90px;">Bokade</th>
                    @if($showWaitTimes)
                        <th style="width: 110px;">Väntetid</th>
                    @endif
                    <th style="width: 100px;">Status</th>
                    <th style="width: 250px;">Åtgärder</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tours as $tour)
                    @php
                        $status = $tour->status ?? 'planned';

                        $statusClass = match($status) {
                            'planned' => 'badge-soft badge-soft-warning',
                            'started' => 'badge-soft badge-soft-success',
                            'completed' => 'badge-soft badge-soft-secondary',
                            'cancelled' => 'badge-soft badge-soft-danger',
                            default => 'badge-soft badge-soft-warning',
                        };

                        $statusLabel = $status === 'completed'
                            ? (method_exists($tour, 'completionStatusLabel')
                                ? $tour->completionStatusLabel()
                                : 'Avslutad')
                            : match($status) {
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

                    <tr @class(['dashboard-tour-wait-warn' => $showWaitTimes && $waitWarn])>
                        <td>
                            <div class="fw-semibold">{{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}</div>
                            <div class="small-muted">{{ $tour->tour_date ? \Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d') : '-' }}</div>
                        </td>

                        <td>
                            <div class="fw-semibold">{{ $tour->title }}</div>
                            <div class="small-muted">{{ $tour->tourType?->name ?? '-' }}</div>
                        </td>

                        <td>
                            @include('partials.tours.meal-badge', ['tour' => $tour])
                            @include('partials.tours.ferry-badge', ['tour' => $tour])
                        </td>

                        <td>
                            @include('partials.admin.tour-guide-cell', ['tour' => $tour])
                        </td>

                        <td>
                            @if($languageCodes->isEmpty())
                                -
                            @else
                                {{ $languageCodes->implode(' + ') }}
                            @endif
                        </td>

                        <td>
                            <a href="{{ route($prefix . '.tours.show', $tour) }}#tour-bookings" class="fw-bold text-decoration-none">
                                {{ $tour->booked_people_count ?? 0 }}
                            </a>
                        </td>

                        @if($showWaitTimes)
                            <td>
                                @include('partials.admin.tour-wait-time-cell', [
                                    'tour' => $tour,
                                    'waitWarningMinutes' => $waitWarningMinutes ?? 45,
                                ])
                            </td>
                        @endif

                        <td>
                            <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>

                        <td>
                            <div class="toolbar-inline">
                                <a href="{{ route($prefix . '.tours.show', $tour) }}" class="btn btn-sm btn-outline-secondary">
                                    Visa
                                </a>

                                <a href="{{ route($prefix . '.tours.show', $tour) }}#tour-bookings" class="btn btn-sm btn-outline-secondary">
                                    Bokningar
                                </a>

                                <a href="{{ route($prefix . '.tours.edit', $tour) }}" class="btn btn-sm btn-outline-secondary">
                                    Redigera
                                </a>

                                @include('partials.admin.tour-start-form', [
                                    'tour' => $tour,
                                    'prefix' => $prefix,
                                    'buttonLabel' => 'Starta',
                                ])

                                @if(($tour->status ?? null) === 'started' && Route::has($prefix . '.tours.complete'))
                                    <form method="POST" action="{{ route($prefix . '.tours.complete', $tour) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Avsluta</button>
                                    </form>
                                @endif

                                @include('partials.admin.tour-delete-form', [
                                    'tour' => $tour,
                                    'bookingsCount' => $tour->bookings->count(),
                                ])
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $columnCount }}" class="text-center muted py-4">Inga turer hittades.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($tours, 'links'))
        <div class="mt-3">
            {{ $tours->links() }}
        </div>
    @endif
</div>
@endsection