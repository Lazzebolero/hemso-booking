@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Turer</h2>
        <div class="muted">
            {{ ($scope ?? 'upcoming') === 'archive'
                ? 'Arkiv med äldre, avslutade och inställda turer.'
                : 'Aktiva och kommande turer, sorterade med närmaste tur först.' }}
        </div>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.tours.index', ['scope' => 'upcoming']) }}"
           class="btn {{ ($scope ?? 'upcoming') !== 'archive' ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bi bi-calendar-event me-2"></i>Aktiva
        </a>

        <a href="{{ route('admin.tours.index', ['scope' => 'archive']) }}"
           class="btn {{ ($scope ?? 'upcoming') === 'archive' ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bi bi-archive me-2"></i>Arkiv
        </a>

        <a href="{{ route('admin.tours.create') }}" class="btn btn-outline-secondary">
            <i class="bi bi-plus-circle me-2"></i>Ny tur
        </a>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Tid</th>
                    <th>Tur</th>
                    <th>Turtyp</th>
                    <th>Språk</th>
                    <th>Guide</th>
                    <th>Status</th>
                    <th>Max</th>
                    <th class="text-end">Åtgärder</th>
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
                            ->unique()
                            ->values();

                        $onlySwedish = $languageCodes->count() === 1 && $languageCodes->contains('sv');
                        $hasNonSwedish = $languageCodes->reject(fn ($code) => $code === 'sv')->isNotEmpty();
                    @endphp

                    <tr>
                        <td>
                            {{ $tour->tour_date ? \Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d') : '-' }}
                        </td>
                        <td>
                            {{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}
                        </td>
                        <td>
                            <a href="{{ route('admin.tours.show', $tour) }}" class="fw-semibold text-decoration-none">
                                {{ $tour->title }}
                            </a>
                        </td>
                        <td>{{ $tour->tourType?->name ?? '-' }}</td>
                        <td>
                            
						@php
							$languageCodes = $tour->bookings
							->flatMap(fn ($booking) => $booking->languages->pluck('code'))
							->filter()
							->map(fn ($code) => strtoupper($code))
							->unique()
							->values();
						@endphp

						@if($languageCodes->isEmpty())
						<span class="muted">-</span>
							@elseif($languageCodes->count() === 1)
						<span class="badge-soft badge-soft-secondary">
							{{ $languageCodes->first() }}
						</span>
						@else
							<span class="badge-soft badge-soft-danger">
							{{ $languageCodes->implode(' + ') }}
						</span>
						@endif
						</td>
                        <td>{{ $tour->guide?->name ?? 'Ej tilldelad' }}</td>
                        <td>
                            <span class="{{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td>{{ $tour->max_participants }}</td>
                        <td class="text-end">
                            <div class="btn-group">
                                <a href="{{ route('admin.tours.show', $tour) }}" class="btn btn-sm btn-outline-secondary">
                                    Visa
                                </a>

                                @if(($tour->status ?? null) !== 'completed')
                                    <a href="{{ route('admin.tours.edit', $tour) }}" class="btn btn-sm btn-outline-secondary">
                                        Redigera
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center muted py-4">
                            Inga turer hittades i denna vy.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $tours->links() }}
    </div>
</div>
@endsection