@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
    $scope = $scope ?? 'active';
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Bokningar</h2>
        <div class="page-subtitle">Översikt över bokningar och deras status.</div>
    </div>

    <div class="page-actions">
        @if(Route::has($prefix . '.bookings.quick-create'))
            <a class="btn btn-primary" href="{{ route($prefix . '.bookings.quick-create') }}">
                <i class="bi bi-list-ol me-2"></i>Bokningssekvens
            </a>
        @endif
    </div>
</div>

<div class="page-card card-compact mb-3">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
        <div>
            <div class="section-title mb-1">Välj vilka bokningar som ska visas</div>
            <div class="small-muted">
                Aktiva bokningar gäller kommande turer. I arkivet kan du se och rätta bokningar på avslutade och historiska turer.
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-start">
            <a
                href="{{ route($prefix . '.bookings.index', array_merge(request()->except('page', 'scope'), ['scope' => 'active'])) }}"
                class="btn btn-sm {{ $scope === 'active' ? 'btn-primary' : 'btn-outline-secondary' }}"
            >
                Aktiva bokningar
            </a>

            <a
                href="{{ route($prefix . '.bookings.index', array_merge(request()->except('page', 'scope'), ['scope' => 'archive'])) }}"
                class="btn btn-sm {{ $scope === 'archive' ? 'btn-primary' : 'btn-outline-secondary' }}"
            >
                Historiska / avslutade
            </a>
        </div>
    </div>
</div>

<div class="page-card card-compact mb-3">
    <form method="GET" action="{{ route($prefix . '.bookings.index') }}" class="row g-2 align-items-end">
        <input type="hidden" name="scope" value="{{ $scope }}">

        <div class="col-md-4">
            <label class="form-label small muted filter-label" for="booking-search-q">Sök</label>
            <input
                id="booking-search-q"
                type="text"
                name="q"
                value="{{ request('q') }}"
                class="form-control form-control-sm"
                placeholder="Namn, kontakt, telefon, e-post eller faktureras"
            >
        </div>

        <div class="col-md-3">
            <label class="form-label small muted filter-label" for="booking-search-date">Turdatum</label>
            <input
                id="booking-search-date"
                type="date"
                name="date"
                value="{{ request('date') }}"
                class="form-control form-control-sm"
            >
        </div>

        <div class="col-md-3">
            <div class="form-check mt-4">
                <input
                    type="checkbox"
                    class="form-check-input"
                    id="to_be_invoiced_only"
                    name="to_be_invoiced_only"
                    value="1"
                    @checked(request()->boolean('to_be_invoiced_only'))
                >
                <label class="form-check-label" for="to_be_invoiced_only">Endast faktureras</label>
            </div>
        </div>

        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-sm btn-primary w-100" type="submit">Filtrera</button>

            @if(request()->hasAny(['q', 'date', 'to_be_invoiced_only', 'arrival_status']))
                <a
                    href="{{ route($prefix . '.bookings.index', ['scope' => $scope]) }}"
                    class="btn btn-sm btn-outline-secondary w-100"
                >
                    Rensa
                </a>
            @endif
        </div>
    </form>
</div>

<div class="page-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="section-title mb-0">
            {{ $scope === 'archive' ? 'Historiska och avslutade bokningar' : 'Aktiva bokningar' }}
        </div>
        <div class="small-muted">
            {{ $bookings->total() }} bokningar
        </div>
    </div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Tur</th>
                    <th>Datum</th>
                    <th>Bokning</th>
                    <th>Deltagare</th>
                    <th>Status</th>
                    <th style="width: 260px;">Åtgärder</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $booking)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $booking->tour?->title ?? '—' }}</div>
                            @if($booking->tour)
                                <a href="{{ route($prefix . '.tours.show', $booking->tour) }}" class="small-muted">
                                    Visa tur
                                </a>
                            @endif
                        </td>
                        <td>{{ $booking->tour?->tour_date ? \Carbon\Carbon::parse($booking->tour->tour_date)->format('Y-m-d') : '—' }}</td>
                        <td>{{ $booking->booking_name ?? '—' }}</td>
                        <td>{{ $booking->total_count ?? 0 }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                <span>{{ $booking->status ?? '—' }}</span>
                                @if($booking->to_be_invoiced)
                                    @include('partials.bookings.invoice-badge', ['booking' => $booking])
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="toolbar-inline">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route($prefix . '.bookings.edit', $booking) }}">
                                    Redigera
                                </a>

                                @if(Route::has($prefix . '.activity-logs.entity-history'))
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route($prefix . '.activity-logs.entity-history', ['booking', $booking->id]) }}">
                                        Historik
                                    </a>
                                @endif

                                @include('partials.bookings.delete-form', [
                                    'booking' => $booking,
                                    'prefix' => $prefix,
                                    'scope' => $scope,
                                ])
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Inga bokningar hittades.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $bookings->links() }}
    </div>
</div>
@endsection
