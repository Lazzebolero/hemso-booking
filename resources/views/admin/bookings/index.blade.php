@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Bokningar</h2>
        <div class="muted">
            {{ ($scope ?? 'active') === 'archive'
                ? 'Arkiv med äldre, avslutade och avbokade bokningar.'
                : 'Aktiva bokningar från idag och framåt.' }}
        </div>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.bookings.index', ['scope' => 'active']) }}"
           class="btn {{ ($scope ?? 'active') !== 'archive' ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bi bi-journal-check me-2"></i>Aktiva
        </a>

        <a href="{{ route('admin.bookings.index', ['scope' => 'archive']) }}"
           class="btn {{ ($scope ?? 'active') === 'archive' ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bi bi-archive me-2"></i>Arkiv
        </a>

        <a href="{{ route('admin.bookings.create') }}" class="btn btn-outline-secondary">
            <i class="bi bi-plus-circle me-2"></i>Ny bokning
        </a>
    </div>
</div>

<div class="page-card mb-4">
    <form method="GET" action="{{ route('admin.bookings.index') }}" class="row g-3 align-items-end">
        <input type="hidden" name="scope" value="{{ $scope ?? 'active' }}">

        <div class="col-md-4">
            <label class="form-label">Sök</label>
            <input
                type="text"
                name="q"
                class="form-control"
                value="{{ request('q') }}"
                placeholder="Sök på bokning, kontakt, telefon eller e-post"
            >
        </div>

        <div class="col-md-3">
            <label class="form-label">Datum</label>
            <input type="date" name="date" class="form-control" value="{{ request('date') }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">Ankomststatus</label>
            <select name="arrival_status" class="form-select">
                <option value="">Alla</option>
                <option value="booked" @selected(request('arrival_status') === 'booked')>Bokad</option>
                <option value="arrived" @selected(request('arrival_status') === 'arrived')>Anlänt</option>
                <option value="no_show" @selected(request('arrival_status') === 'no_show')>No-show</option>
                <option value="late_cancel" @selected(request('arrival_status') === 'late_cancel')>Sen avbokning</option>
            </select>
        </div>

        <div class="col-md-2">
            <div class="form-check mt-4 pt-2">
                <input class="form-check-input" type="checkbox" name="waitlist_only" value="1" id="waitlist_only" @checked(request()->boolean('waitlist_only'))>
                <label class="form-check-label" for="waitlist_only">
                    Endast väntelista
                </label>
            </div>
        </div>

        <div class="col-12 d-flex gap-2 flex-wrap">
            <button class="btn btn-primary">
                <i class="bi bi-funnel me-2"></i>Filtrera
            </button>

            <a href="{{ route('admin.bookings.index', ['scope' => $scope ?? 'active']) }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-circle me-2"></i>Rensa
            </a>

            <a href="{{ route('admin.bookings.export-csv', request()->query()) }}" class="btn btn-outline-secondary">
                <i class="bi bi-download me-2"></i>CSV-export
            </a>
        </div>
    </form>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Bokning</th>
                    <th>Tur</th>
                    <th>Turtyp</th>
                    <th>Datum</th>
                    <th>Språk</th>
                    <th>Kontakt</th>
                    <th>Antal</th>
                    <th>Status</th>
                    <th>Ankomst</th>
                    <th>Väntelista</th>
                    <th class="text-end">Åtgärder</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $booking)
                    @php
                        $statusClass = match($booking->status) {
                            'confirmed' => 'badge-soft badge-soft-success',
                            'preliminary' => 'badge-soft badge-soft-warning',
                            'completed' => 'badge-soft badge-soft-secondary',
                            'cancelled' => 'badge-soft badge-soft-danger',
                            default => 'badge-soft badge-soft-warning',
                        };

                        $statusLabel = match($booking->status) {
                            'confirmed' => 'Bekräftad',
                            'preliminary' => 'Preliminär',
                            'completed' => 'Slutförd',
                            'cancelled' => 'Avbokad',
                            default => ucfirst($booking->status ?? '-'),
                        };

                        $arrivalLabel = match($booking->arrival_status) {
                            'booked' => 'Bokad',
                            'arrived' => 'Anlänt',
                            'no_show' => 'No-show',
                            'late_cancel' => 'Sen avbokning',
                            default => '-',
                        };
                    @endphp

                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $booking->booking_name }}</div>
                            @if($booking->duplicate_warning)
                                <div class="small text-warning">Möjlig dubblett</div>
                            @endif
                        </td>
                        <td>{{ $booking->tour?->title ?? '-' }}</td>
                        <td>{{ $booking->tour?->tourType?->name ?? '-' }}</td>
                        <td>
                            {{ $booking->tour?->tour_date ? \Carbon\Carbon::parse($booking->tour->tour_date)->format('Y-m-d') : '-' }}
                        </td>
                        <td>
                            @php
                                $languageCodes = $booking->languages->pluck('code')->map(fn ($code) => strtoupper($code));
                            @endphp

                            @if($languageCodes->isEmpty())
                                <span class="muted">-</span>
                            @else
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($languageCodes as $code)
                                        <span class="badge-soft badge-soft-secondary">{{ $code }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td>
                            <div>{{ $booking->contact_name ?: '-' }}</div>
                            @if($booking->phone)
                                <div class="small muted">{{ $booking->phone }}</div>
                            @endif
                        </td>
                        <td>{{ $booking->total_count }}</td>
                        <td>
                            <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                        <td>{{ $arrivalLabel }}</td>
                        <td>
                            @if($booking->is_waitlist)
                                <span class="badge-soft badge-soft-warning">Ja</span>
                            @else
                                <span class="badge-soft badge-soft-secondary">Nej</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn-sm btn-outline-secondary">
                                    Redigera
                                </a>

                                <form method="POST" action="{{ route('admin.bookings.destroy', $booking) }}" onsubmit="return confirm('Ta bort bokningen?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Ta bort
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center muted py-4">
                            Inga bokningar hittades i denna vy.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $bookings->links() }}
    </div>
</div>
@endsection