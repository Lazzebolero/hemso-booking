@props([
    'tour',
    'prefix',
])

@php
    $tourStatus = $tour->status ?? 'planned';
    $isOngoingTour = $tourStatus === 'started';
    $isCompletedTour = $tourStatus === 'completed';
    $isPastTour = $tour->tour_date && $tour->tour_date->toDateString() < now()->toDateString();
    $isRetroactiveTour = $isCompletedTour || $isPastTour || $isOngoingTour;
@endphp

<div class="page-card mb-4" id="tour-bookings">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div>
            <div class="section-title mb-1">Bokningar på turen</div>
            <div class="small-muted">
                @if($isOngoingTour)
                    Pågående tur — se och uppdatera varje bokning här.
                @elseif($isCompletedTour || $isPastTour)
                    Genomförd tur — bokningar kan rättas i efterhand.
                @else
                    Alla bokningar som hör till den här turen.
                @endif
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge bg-light text-dark border">{{ $tour->bookings->count() }} bokningar</span>

            @if(Route::has($prefix . '.bookings.create'))
                <a href="{{ route($prefix . '.bookings.create', ['tour_id' => $tour->id]) }}" class="btn btn-sm btn-outline-secondary">
                    Lägg till bokning
                </a>
            @endif
        </div>
    </div>

    @if($tour->bookings->isNotEmpty())
        <div class="table-responsive-modern">
            <table class="table-modern dashboard-table">
                <thead>
                    <tr>
                        <th>Bokning</th>
                        <th>Kontakt</th>
                        <th>Språk</th>
                        <th>Deltagare</th>
                        <th>Status</th>
                        <th style="width: 220px;">Åtgärder</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tour->bookings as $booking)
                        @php
                            $bookingStatus = $booking->status ?? 'confirmed';

                            $bookingStatusClass = match ($bookingStatus) {
                                'cancelled' => 'badge-soft badge-soft-danger',
                                'confirmed' => 'badge-soft badge-soft-success',
                                'preliminary' => 'badge-soft badge-soft-warning',
                                'completed' => 'badge-soft badge-soft-secondary',
                                default => 'badge-soft badge-soft-secondary',
                            };

                            $bookingStatusLabel = match ($bookingStatus) {
                                'cancelled' => 'Avbokad',
                                'confirmed' => 'Bekräftad',
                                'preliminary' => 'Preliminär',
                                'completed' => 'Klar',
                                default => ucfirst($bookingStatus),
                            };

                            $arrivalStatusLabel = match ($booking->arrival_status ?? 'booked') {
                                'arrived' => 'Ankommen',
                                'no_show' => 'No-show',
                                'late_cancel' => 'Sen avbokning',
                                default => null,
                            };

                            $bookingLanguages = $booking->languages
                                ->pluck('code')
                                ->filter()
                                ->map(fn ($code) => strtoupper($code))
                                ->implode(', ');
                        @endphp

                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $booking->booking_name ?? 'Bokning #' . $booking->id }}</div>
                                @if($booking->created_at)
                                    <div class="small-muted">Bokad {{ $booking->created_at->format('Y-m-d H:i') }}</div>
                                @endif
                                @if(!empty($booking->notes))
                                    <div class="small-muted">{{ \Illuminate\Support\Str::limit($booking->notes, 80) }}</div>
                                @endif
                            </td>

                            <td>
                                <div>{{ $booking->contact_name ?: '-' }}</div>
                                @if(!empty($booking->phone))
                                    <div class="small-muted">{{ $booking->phone }}</div>
                                @endif
                                @if(!empty($booking->email))
                                    <div class="small-muted">{{ $booking->email }}</div>
                                @endif
                            </td>

                            <td>{{ $bookingLanguages ?: '-' }}</td>

                            <td>
                                <div class="fw-semibold">{{ (int) $booking->total_count }} totalt</div>
                                <div class="small-muted">
                                    M {{ (int) $booking->men_count }},
                                    K {{ (int) $booking->women_count }},
                                    U {{ (int) $booking->youth_count }},
                                    B {{ (int) $booking->child_count }}
                                </div>
                            </td>

                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="{{ $bookingStatusClass }}">{{ $bookingStatusLabel }}</span>

                                    @if($booking->is_walk_in)
                                        <span class="badge-soft badge-soft-secondary">Drop-in</span>
                                    @endif

                                    @if($booking->includes_meal)
                                        <span class="tour-meal-badge">
                                            <i class="bi bi-cup-hot-fill" aria-hidden="true"></i>
                                            Med mat
                                        </span>
                                    @endif

                                    @if($booking->to_be_invoiced)
                                        @include('partials.bookings.invoice-badge', ['booking' => $booking])
                                    @endif

                                    @if($arrivalStatusLabel)
                                        <span class="badge-soft badge-soft-secondary">{{ $arrivalStatusLabel }}</span>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div class="toolbar-inline">
                                    @if(Route::has($prefix . '.bookings.edit'))
                                        <a href="{{ route($prefix . '.bookings.edit', ['booking' => $booking, 'from_tour' => 1]) }}" class="btn btn-sm btn-outline-secondary">
                                            Redigera
                                        </a>
                                    @endif

                                    @include('partials.bookings.delete-form', [
                                        'booking' => $booking,
                                        'prefix' => $prefix,
                                        'fromTour' => true,
                                    ])
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bi bi-journal-text"></i>
            </div>
            <div class="fw-semibold">Inga bokningar registrerade</div>
            <div class="small-muted">
                @if($isRetroactiveTour)
                    Lägg till bokningar eller uppdatera dem när de finns.
                @else
                    När bokningar skapas för turen visas de här.
                @endif
            </div>
            @if(Route::has($prefix . '.bookings.create'))
                <a href="{{ route($prefix . '.bookings.create', ['tour_id' => $tour->id]) }}" class="btn btn-sm btn-primary mt-3">
                    Lägg till bokning
                </a>
            @endif
        </div>
    @endif
</div>
