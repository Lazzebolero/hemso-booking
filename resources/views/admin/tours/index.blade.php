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

        <a href="{{ route($prefix . '.tours.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Ny tur
        </a>
    </div>
</div>

<div class="page-card card-compact mb-3">
    <div class="scope-switch mb-3">
        <a
            href="{{ route($prefix . '.tours.index', array_merge(request()->except('page', 'scope'), ['scope' => 'upcoming'])) }}"
            class="scope-pill {{ ($scope ?? 'upcoming') === 'upcoming' ? 'scope-pill-active' : '' }}"
        >
            Aktiva / kommande
        </a>

        <a
            href="{{ route($prefix . '.tours.index', array_merge(request()->except('page', 'scope'), ['scope' => 'archive'])) }}"
            class="scope-pill {{ ($scope ?? 'upcoming') === 'archive' ? 'scope-pill-active' : '' }}"
        >
            Arkiv
        </a>
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

<div class="page-card card-compact">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="section-title mb-0">Lista</div>
        <div class="small muted">
            {{ method_exists($tours, 'total') ? $tours->total() : count($tours) }} turer
        </div>
    </div>

    <div class="table-responsive-modern">
        <table class="table-modern dashboard-table">
            <thead>
                <tr>
                    <th style="width: 130px;">Tid</th>
                    <th>Tur</th>
                    <th style="width: 150px;">Guide</th>
                    <th style="width: 80px;">Språk</th>
                    <th style="width: 90px;">Bokade</th>
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
                            <div class="fw-semibold">{{ $tour->title }}</div>
                            <div class="small-muted">{{ $tour->tourType?->name ?? '-' }}</div>
                        </td>

                        <td>{{ $tour->guide?->name ?? 'Ej tilldelad' }}</td>

                        <td>
                            @if($languageCodes->isEmpty())
                                -
                            @else
                                {{ $languageCodes->implode(' + ') }}
                            @endif
                        </td>

                        <td>{{ $tour->booked_people_count ?? 0 }}</td>

                        <td>
                            <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>

                        <td>
                            <div class="toolbar-inline">
                                <a href="{{ route($prefix . '.tours.show', $tour) }}" class="btn btn-sm btn-outline-secondary">
                                    Visa
                                </a>

                                @if(($tour->status ?? null) !== 'completed')
                                    <a href="{{ route($prefix . '.tours.edit', $tour) }}" class="btn btn-sm btn-outline-secondary">
                                        Redigera
                                    </a>
                                @endif

                                @if(($tour->status ?? null) === 'planned' && Route::has($prefix . '.tours.start'))
                                    <form method="POST" action="{{ route($prefix . '.tours.start', $tour) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Starta</button>
                                    </form>
                                @endif

                                @if(($tour->status ?? null) === 'started' && Route::has($prefix . '.tours.complete'))
                                    <form method="POST" action="{{ route($prefix . '.tours.complete', $tour) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Avsluta</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center muted py-4">Inga turer hittades.</td>
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