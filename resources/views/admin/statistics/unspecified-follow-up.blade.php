@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();

    $scopeLabels = [
        'all' => 'Alla',
        'upcoming' => 'Kommande',
        'completed' => 'Genomförda',
    ];

    $bookingStatusLabels = [
        'preliminary' => 'Preliminär',
        'confirmed' => 'Bekräftad',
        'cancelled' => 'Avbokad',
        'completed' => 'Genomförd',
        'no_show' => 'No-show',
        'late_cancel' => 'Sen avbokning',
    ];
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Ospecificerade bokningar</h2>
        <div class="page-subtitle">
            Bokningar där deltagare saknar fördelning män/kvinnor/ungdom/barn.
            Fyll i M/K/U/B direkt i listan — totalen är låst och ospecificerade räknas om automatiskt.
        </div>
    </div>

    <div class="page-actions d-flex gap-2 flex-wrap">
        @if(Route::has($prefix . '.statistics.index'))
            <a href="{{ route($prefix . '.statistics.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-bar-chart me-2"></i>Till statistik
            </a>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="page-card compact-card mb-4">
    <form method="GET" action="{{ route($prefix . '.statistics.unspecified-follow-up') }}" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Från</label>
            <input type="date" name="from" class="form-control" value="{{ $from->toDateString() }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Till</label>
            <input type="date" name="to" class="form-control" value="{{ $to->toDateString() }}">
        </div>
        <div class="col-md-3">
            <label class="form-label d-block">Turstatus</label>
            <div class="d-flex flex-wrap gap-2">
                @foreach($scopeLabels as $scopeKey => $scopeLabel)
                    <a
                        href="{{ route($prefix . '.statistics.unspecified-follow-up', array_merge(request()->except('page', 'scope'), ['scope' => $scopeKey, 'from' => $from->toDateString(), 'to' => $to->toDateString()])) }}"
                        class="btn btn-sm {{ ($scope ?? 'upcoming') === $scopeKey ? 'btn-primary' : 'btn-outline-secondary' }}"
                    >
                        {{ $scopeLabel }}
                    </a>
                @endforeach
            </div>
        </div>
        <div class="col-md-3">
            <input type="hidden" name="scope" value="{{ $scope ?? 'upcoming' }}">
            <button type="submit" class="btn btn-primary w-100">Visa</button>
        </div>
    </form>
</div>

<div class="stats-kpi-grid mb-4">
    <div class="stats-card premium-kpi">
        <div class="stats-label">Ospecificerade personer</div>
        <div class="stats-value">{{ $summary['unspecified_people'] ?? 0 }}</div>
        <div class="stats-subtext">I vald period och filter</div>
    </div>
    <div class="stats-card premium-kpi">
        <div class="stats-label">Bokningar</div>
        <div class="stats-value">{{ $summary['booking_count'] ?? 0 }}</div>
        <div class="stats-subtext">Med ospecificerade deltagare</div>
    </div>
    <div class="stats-card premium-kpi">
        <div class="stats-label">Turer</div>
        <div class="stats-value">{{ $summary['tour_count'] ?? 0 }}</div>
        <div class="stats-subtext">Berörda turer</div>
    </div>
</div>

<div class="page-card mb-4">
    <div class="section-title mb-3">Bokningar att komplettera</div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Tur</th>
                    <th>Bokning</th>
                    <th>Guide</th>
                    <th style="min-width: 280px;">Fördela M / K / U / B</th>
                    <th style="width: 70px;">Total</th>
                    <th style="width: 60px;">O</th>
                    <th>Status</th>
                    <th style="width: 100px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $booking)
                    @php
                        $tour = $booking->tour;
                        $tourDate = $tour?->tour_date
                            ? \Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d')
                            : '—';
                        $tourTime = ! empty($tour?->start_time) ? substr($tour->start_time, 0, 5) : '';
                        $canQuickUpdate = Route::has($prefix . '.bookings.quick-update-participants');
                        $useOldInput = (int) old('follow_up_booking_id') === (int) $booking->id;
                    @endphp
                    <tr>
                        <td>
                            @if($tour && Route::has($prefix . '.tours.show'))
                                <a href="{{ route($prefix . '.tours.show', $tour) }}" class="text-decoration-none">
                                    <div class="fw-semibold">{{ $tourDate }} {{ $tourTime }}</div>
                                    <div class="small-muted">{{ $tour->title }}</div>
                                </a>
                            @else
                                <div class="fw-semibold">{{ $tourDate }} {{ $tourTime }}</div>
                                <div class="small-muted">{{ $tour?->title ?? '—' }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $booking->booking_name ?: '—' }}</div>
                            @if($booking->contact_name)
                                <div class="small-muted">{{ $booking->contact_name }}</div>
                            @endif
                        </td>
                        <td>{{ $tour?->guide?->name ?? '—' }}</td>
                        <td>
                            @if($canQuickUpdate)
                                <form
                                    method="POST"
                                    action="{{ route($prefix . '.bookings.quick-update-participants', $booking) }}"
                                    class="unspecified-follow-up-form d-flex flex-wrap align-items-end gap-2"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $booking->status }}">
                                    <input type="hidden" name="return_to" value="unspecified-follow-up">
                                    <input type="hidden" name="follow_up_booking_id" value="{{ $booking->id }}">
                                    <input type="hidden" name="follow_up_from" value="{{ $from->toDateString() }}">
                                    <input type="hidden" name="follow_up_to" value="{{ $to->toDateString() }}">
                                    <input type="hidden" name="follow_up_scope" value="{{ $scope ?? 'upcoming' }}">
                                    @if($bookings->currentPage() > 1)
                                        <input type="hidden" name="follow_up_page" value="{{ $bookings->currentPage() }}">
                                    @endif

                                    @foreach(['men_count' => 'M', 'women_count' => 'K', 'youth_count' => 'U', 'child_count' => 'B'] as $field => $label)
                                        <label class="unspecified-follow-up-field mb-0">
                                            <span class="small-muted">{{ $label }}</span>
                                            <input
                                                type="number"
                                                min="0"
                                                inputmode="numeric"
                                                name="{{ $field }}"
                                                class="form-control form-control-sm @error($field) is-invalid @enderror"
                                                value="{{ $useOldInput ? old($field, 0) : (int) $booking->{$field} }}"
                                                style="width: 4.5rem;"
                                            >
                                        </label>
                                    @endforeach

                                    <button type="submit" class="btn btn-sm btn-primary">
                                        Spara
                                    </button>
                                </form>
                            @else
                                <span class="small-muted">
                                    M{{ $booking->men_count }}
                                    K{{ $booking->women_count }}
                                    U{{ $booking->youth_count }}
                                    B{{ $booking->child_count }}
                                </span>
                            @endif
                        </td>
                        <td class="fw-semibold">{{ $booking->total_count }}</td>
                        <td>
                            <span class="fw-bold text-warning">O{{ $booking->unspecified_count }}</span>
                        </td>
                        <td>{{ $bookingStatusLabels[$booking->status] ?? $booking->status ?? '—' }}</td>
                        <td class="text-end">
                            @if(Route::has($prefix . '.bookings.edit'))
                                <a href="{{ route($prefix . '.bookings.edit', $booking) }}" class="btn btn-sm btn-outline-secondary">
                                    Mer
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center small-muted py-4">
                            Inga bokningar med ospecificerade deltagare i vald period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($bookings->hasPages())
        <div class="mt-3">
            {{ $bookings->links() }}
        </div>
    @endif
</div>
@endsection
