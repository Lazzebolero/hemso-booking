@extends('layouts.guide')

@section('content')
@php
    $bookings = collect($tour->bookings ?? []);
    $activeBookings = $bookings->whereNotIn('status', ['cancelled']);

    $bookingCount = $activeBookings->count();
    $bookedCount = $activeBookings->sum('total_count');

    $maxParticipants = $tour->max_participants ?? 0;

    $status = $tour->status ?? 'planned';

    $statusClass = match ($status) {
        'planned' => 'badge-soft badge-soft-warning',
        'started' => 'badge-soft badge-soft-success',
        'completed' => 'badge-soft badge-soft-secondary',
        'cancelled' => 'badge-soft badge-soft-danger',
        default => 'badge-soft badge-soft-secondary',
    };

    $statusLabel = match ($status) {
        'planned' => 'Planerad',
        'started' => 'Pågår',
        'completed' => 'Avslutad',
        'cancelled' => 'Inställd',
        default => ucfirst($status),
    };

    $startedAtLabel = !empty($tour->started_at) ? \Carbon\Carbon::parse($tour->started_at)->format('H:i') : null;
    $endedAtLabel = !empty($tour->ended_at) ? \Carbon\Carbon::parse($tour->ended_at)->format('H:i') : null;

    $plannedDurationMinutes = null;
    $estimatedEndTime = null;
    $remainingToEnd = null;

    if (!empty($tour->start_time) && !empty($tour->end_time)) {
        try {
            $normalizedStart = strlen($tour->start_time) === 5 ? $tour->start_time . ':00' : $tour->start_time;
            $normalizedEnd = strlen($tour->end_time) === 5 ? $tour->end_time . ':00' : $tour->end_time;

            $plannedStart = \Carbon\Carbon::createFromFormat('H:i:s', $normalizedStart);
            $plannedEnd = \Carbon\Carbon::createFromFormat('H:i:s', $normalizedEnd);

            $plannedDurationMinutes = $plannedStart->diffInMinutes($plannedEnd, false);

            if ($status === 'started' && !empty($tour->started_at) && $plannedDurationMinutes > 0) {
                $actualEnd = \Carbon\Carbon::parse($tour->started_at)->addMinutes($plannedDurationMinutes);
                $estimatedEndTime = $actualEnd->format('H:i');

                $remainingMinutes = (int) now()->diffInMinutes($actualEnd, false);

                if ($remainingMinutes > 60) {
                    $hours = floor($remainingMinutes / 60);
                    $minutes = $remainingMinutes % 60;
                    $remainingToEnd = $minutes > 0 ? $hours . 'h ' . $minutes . ' min kvar' : $hours . 'h kvar';
                } elseif ($remainingMinutes > 0) {
                    $remainingToEnd = $remainingMinutes . ' min kvar';
                } elseif ($remainingMinutes === 0) {
                    $remainingToEnd = 'slutar nu';
                } else {
                    $remainingToEnd = 'borde vara klar';
                }
            }
        } catch (\Throwable $e) {
            $plannedDurationMinutes = null;
            $estimatedEndTime = null;
            $remainingToEnd = null;
        }
    }

    $languageCodes = $bookings
        ->flatMap(function ($booking) {
            return collect($booking->languages ?? [])->pluck('code');
        })
        ->filter()
        ->map(fn ($code) => strtoupper($code))
        ->unique()
        ->values();
@endphp

@php
    $tourMen = (int) $bookings->sum('men_count');
    $tourWomen = (int) $bookings->sum('women_count');
    $tourYouth = (int) $bookings->sum('youth_count');
    $tourChildren = (int) $bookings->sum('child_count');
    $tourUnspecified = (int) $bookings->sum('unspecified_count');
    $tourCategoryShort = "M{$tourMen} K{$tourWomen} U{$tourYouth} B{$tourChildren}";
    if ($tourUnspecified > 0) {
        $tourCategoryShort .= " O{$tourUnspecified}";
    }
@endphp


<div
    data-guide-tour-root
    data-tour-id="{{ $tour->id }}"
    data-tour-status="{{ $status }}"
    data-start-url="{{ route('guide.tours.start', $tour) }}"
    data-headcount-url="{{ route('guide.tours.adjust-headcount', $tour) }}"
    data-complete-url="{{ route('guide.tours.complete', $tour) }}"
    data-server-started-at="{{ $startedAtLabel ?? '' }}"
>
<div class="page-card mb-3 guide-tour-header">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h2 class="page-title mb-1">{{ $tour->title }}</h2>

            <div class="page-subtitle">
                {{ $tour->tour_date ? \Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d') : '-' }}
                • {{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}
                @if(!empty($tour->end_time))
                    – {{ substr($tour->end_time, 0, 5) }}
                @endif
                • {{ $tour->tourType?->name ?? '-' }}
            </div>
        </div>

        <div class="d-flex flex-column align-items-end gap-2">
            <span data-guide-tour-status-badge class="{{ $statusClass }}">{{ $statusLabel }}</span>
            <div data-guide-tour-sync-pending class="guide-tour-sync-pending" hidden>
                <i class="bi bi-cloud-upload"></i>
                Synkas när nätet är tillbaka
            </div>
        </div>
    </div>

    <div class="toolbar-inline mt-3">
        @if($languageCodes->isEmpty())
            <span class="badge-soft badge-soft-secondary">Språk: -</span>
        @elseif($languageCodes->count() === 1)
            <span class="badge-soft badge-soft-secondary">Språk: {{ $languageCodes->first() }}</span>
        @else
            <span class="badge-soft badge-soft-danger">Språk: {{ $languageCodes->implode(' + ') }}</span>
        @endif

        @if(!empty($tour->guide?->name))
            <span class="badge-soft badge-soft-secondary">Guide: {{ $tour->guide->name }}</span>
        @endif
    </div>

    <div class="guide-tour-quick-stats mt-3">
        <span><strong>{{ $bookingCount }}</strong> bokningar</span>
        <span class="guide-tour-quick-stats-sep">·</span>
        <span><strong>{{ $bookedCount }}</strong> personer</span>
        <span class="tour-category-short">{{ $tourCategoryShort }}</span>
    </div>

    <div class="guide-tour-header-actions mt-3">
        @if($status === 'planned')
            <div class="guide-start-panel mb-3" data-guide-start-panel>
                <div class="small-muted mb-1">Bokat i systemet: <strong data-guide-booked-count>{{ $bookedCount }}</strong></div>
                <label class="form-label mb-1" for="actual_on_site_count">Antal på plats</label>
                <input
                    type="number"
                    min="1"
                    id="actual_on_site_count"
                    class="form-control form-control-lg text-center fw-bold"
                    value="{{ old('actual_on_site_count', $bookedCount) }}"
                    inputmode="numeric"
                >
                <div class="guide-headcount-buttons mt-2 mb-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-headcount-adjust="-3">−3</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-headcount-adjust="-1">−1</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-headcount-adjust="1">+1</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-headcount-adjust="3">+3</button>
                </div>
                <div class="small-muted mb-3" data-headcount-diff-wrap hidden>
                    Avvikelse: <strong data-headcount-diff>0</strong>
                </div>
            </div>
        @elseif($status === 'started')
            <div class="guide-headcount-live mb-3" data-guide-headcount-live>
                <div class="small-muted mb-1">
                    Personer på turen: <strong data-guide-live-count>{{ $bookedCount }}</strong>
                    @if($tour->booked_total_at_start)
                        <span class="ms-1">(Bokat var: {{ (int) $tour->booked_total_at_start }})</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('guide.tours.adjust-headcount', $tour) }}" data-offline-queue data-guide-headcount-form>
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="actual_on_site_count" value="{{ $bookedCount }}" data-guide-live-count-hidden>
                    <div class="guide-headcount-buttons">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-live-adjust="-5">−5</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-live-adjust="-1">−1</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-live-adjust="1">+1</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-live-adjust="5">+5</button>
                    </div>
                </form>
            </div>
        @endif

        <div data-guide-tour-primary-action class="guide-tour-primary-action">
            @if($status === 'planned')
                <form method="POST" action="{{ route('guide.tours.start', $tour) }}" id="guide-start-tour-form" data-offline-queue data-guide-tour-action-form>
                    @csrf
                    <input type="hidden" name="actual_on_site_count" value="{{ $bookedCount }}" data-guide-start-count-hidden>
                    <button type="submit" class="btn btn-success btn-lg w-100" data-guide-start-submit>
                        <i class="bi bi-play-circle me-2"></i>Starta med <span data-guide-start-count-label>{{ $bookedCount }}</span> personer
                    </button>
                </form>
            @elseif($status === 'started')
                <form method="POST" action="{{ route('guide.tours.complete', $tour) }}" data-offline-queue data-guide-tour-action-form>
                    @csrf
                    <button type="submit" class="btn btn-danger btn-lg w-100">
                        <i class="bi bi-stop-circle me-2"></i>Avsluta tur
                    </button>
                </form>
            @elseif($status === 'completed')
                <div class="alert alert-success mb-0 text-center">
                    <i class="bi bi-check-circle me-2"></i>
                    @if(method_exists($tour, 'wasAutoCompleted') && $tour->wasAutoCompleted())
                        Tur avslutad automatiskt.
                    @else
                        Tur avslutad.
                    @endif
                </div>
            @endif
        </div>

        <div class="guide-tour-secondary-actions">
            <a href="{{ route('guide.dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Tillbaka
            </a>

            <a href="{{ route('guide.reports.create') }}" class="btn btn-outline-secondary">
                <i class="bi bi-exclamation-triangle me-2"></i>Felrapport
            </a>

            @if(Route::has('guide.memories.create'))
                <a href="{{ route('guide.memories.create', ['tour' => $tour->id]) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-journal-text me-2"></i>Spara minne
                </a>
            @endif
        </div>
    </div>
</div>

<div class="page-card booking-mobile-section mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <div class="section-title mb-1">Bokningar</div>
            <div class="small-muted">Tryck på en grupp för att ändra antal.</div>
        </div>
    </div>

    <div class="booking-card-list">
        @forelse($bookings as $booking)
            @php
                $rowStatus = $booking->status ?? 'confirmed';

                $rowClass = match ($rowStatus) {
                    'cancelled' => 'badge-soft badge-soft-danger',
                    'confirmed' => 'badge-soft badge-soft-success',
                    'preliminary' => 'badge-soft badge-soft-warning',
                    'completed' => 'badge-soft badge-soft-secondary',
                    default => 'badge-soft badge-soft-secondary',
                };

                $rowStatusLabel = match ($rowStatus) {
                    'cancelled' => 'Avbokad',
                    'confirmed' => 'Bekräftad',
                    'preliminary' => 'Prel.',
                    'completed' => 'Klar',
                    default => ucfirst($rowStatus),
                };

                $bookingTitle = $booking->booking_name ?? $booking->contact_name ?? 'Bokning #' . $booking->id;
                $bookingTotal = (int) ($booking->total_count ?? 0);
                $bookingLanguages = collect($booking->languages ?? [])
                    ->pluck('code')
                    ->filter()
                    ->map(fn ($code) => strtoupper($code))
                    ->implode(', ');
                $bookingCategoryShort = 'M' . (int) $booking->men_count
                    . ' K' . (int) $booking->women_count
                    . ' U' . (int) $booking->youth_count
                    . ' B' . (int) $booking->child_count;
                if ((int) ($booking->unspecified_count ?? 0) > 0) {
                    $bookingCategoryShort .= ' O' . (int) $booking->unspecified_count;
                }
            @endphp

            <div class="booking-mobile-card">
                <button type="button"
                        class="booking-mobile-summary"
                        data-booking-toggle="booking-edit-{{ $booking->id }}"
                        aria-expanded="false">
                    <div class="booking-mobile-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>

                    <div class="booking-mobile-main">
                        <div class="booking-mobile-title-row">
                            <div class="booking-mobile-title">{{ $bookingTitle }}</div>
                            <span class="{{ $rowClass }}">{{ $rowStatusLabel }}</span>
                        </div>

                        <div class="booking-mobile-people">
                            <span class="booking-mobile-people-count">{{ $bookingTotal }} personer</span>
                            @if($bookingTotal > 0)
                                <span class="booking-mobile-people-breakdown">{{ $bookingCategoryShort }}</span>
                            @endif
                            @if($bookingLanguages)
                                <span class="booking-mobile-people-lang">{{ $bookingLanguages }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="booking-mobile-chevron">
                        <i class="bi bi-chevron-down"></i>
                    </div>
                </button>

                <div id="booking-edit-{{ $booking->id }}" class="booking-mobile-edit" hidden>
                    <form method="POST" action="{{ route('guide.bookings.update-participants', $booking) }}" data-offline-queue>
                        @csrf
                        @method('PATCH')

                        <div class="booking-edit-grid">
                            <label class="booking-edit-field">
                                <span>Män</span>
                                <input type="number"
                                       min="0"
                                       inputmode="numeric"
                                       name="men_count"
                                       class="form-control"
                                       value="{{ (int) $booking->men_count > 0 ? $booking->men_count : '' }}">
                            </label>

                            <label class="booking-edit-field">
                                <span>Kvinnor</span>
                                <input type="number"
                                       min="0"
                                       inputmode="numeric"
                                       name="women_count"
                                       class="form-control"
                                       value="{{ (int) $booking->women_count > 0 ? $booking->women_count : '' }}">
                            </label>

                            <label class="booking-edit-field">
                                <span>Ungdomar</span>
                                <input type="number"
                                       min="0"
                                       inputmode="numeric"
                                       name="youth_count"
                                       class="form-control"
                                       value="{{ (int) $booking->youth_count > 0 ? $booking->youth_count : '' }}">
                            </label>

                            <label class="booking-edit-field">
                                <span>Barn</span>
                                <input type="number"
                                       min="0"
                                       inputmode="numeric"
                                       name="child_count"
                                       class="form-control"
                                       value="{{ (int) $booking->child_count > 0 ? $booking->child_count : '' }}">
                            </label>

                            <label class="booking-edit-field">
                                <span>Ospec.</span>
                                <input type="number"
                                       min="0"
                                       inputmode="numeric"
                                       name="unspecified_count"
                                       class="form-control"
                                       value="{{ (int) ($booking->unspecified_count ?? 0) > 0 ? $booking->unspecified_count : '' }}">
                            </label>

                            <label class="booking-edit-field booking-edit-status">
                                <span>Status</span>
                                <select name="status" class="form-select">
                                    <option value="preliminary" @selected($rowStatus === 'preliminary')>Preliminär</option>
                                    <option value="confirmed" @selected($rowStatus === 'confirmed')>Bekräftad</option>
                                    <option value="completed" @selected($rowStatus === 'completed')>Klar</option>
                                    <option value="cancelled" @selected($rowStatus === 'cancelled')>Avbokad</option>
                                </select>
                            </label>
                        </div>

                        @if(!empty($booking->notes))
                            <div class="booking-mobile-notes mt-2">
                                {{ $booking->notes }}
                            </div>
                        @endif

                        <div class="booking-edit-actions">
                            <button type="button"
                                    class="btn btn-outline-secondary"
                                    data-booking-toggle="booking-edit-{{ $booking->id }}">
                                Avbryt
                            </button>

                            <button type="submit"
                                    class="btn btn-primary"
                                    @disabled($status === 'completed')>
                                <i class="bi bi-check2-circle me-2"></i>Spara grupp
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="fw-semibold">Inga bokningar ännu</div>
                <div class="small-muted">När grupper bokas visas de här.</div>
            </div>
        @endforelse
    </div>
</div>

<div class="page-card guide-tour-status-panel mb-3">
    <div class="section-title mb-3">Turstatus</div>

    <div class="tour-timeline">
        <div class="tour-step {{ in_array($status, ['planned', 'started', 'completed']) ? 'tour-step-active' : '' }}" data-guide-tour-step="planned">
            <div class="tour-step-dot"></div>
            <div>
                <div class="fw-semibold">Planerad</div>
                <div class="small-muted">
                    {{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}
                </div>
            </div>
        </div>

        <div class="tour-step {{ in_array($status, ['started', 'completed']) ? 'tour-step-active' : '' }}" data-guide-tour-step="started">
            <div class="tour-step-dot"></div>
            <div>
                <div class="fw-semibold">Startad</div>
                <div class="small-muted" data-guide-tour-started-at>{{ $startedAtLabel ?? '-' }}</div>
            </div>
        </div>

        <div class="tour-step {{ $status === 'completed' ? 'tour-step-active' : '' }}" data-guide-tour-step="completed">
            <div class="tour-step-dot"></div>
            <div>
                <div class="fw-semibold">{{ method_exists($tour, 'wasAutoCompleted') && $tour->wasAutoCompleted() ? 'Automatiskt avslutad' : 'Avslutad' }}</div>
                <div class="small-muted" data-guide-tour-ended-at>{{ $endedAtLabel ?? '-' }}</div>
            </div>
        </div>
    </div>

    @if($status === 'started' && ($estimatedEndTime || $remainingToEnd))
        <div class="guide-tour-timing mt-3">
            @if($estimatedEndTime)
                <span>Beräknas klar {{ $estimatedEndTime }}</span>
            @endif
            @if($remainingToEnd)
                <span>{{ $remainingToEnd }}</span>
            @endif
        </div>
    @endif
</div>

<template data-guide-tour-template="start">
    <form method="POST" action="{{ route('guide.tours.start', $tour) }}" data-offline-queue data-guide-tour-action-form>
        @csrf
        <input type="hidden" name="actual_on_site_count" value="{{ $bookedCount }}" data-guide-start-count-hidden>
        <button type="submit" class="btn btn-success btn-lg w-100">
            <i class="bi bi-play-circle me-2"></i>Starta med {{ $bookedCount }} personer
        </button>
    </form>
</template>

<template data-guide-tour-template="complete">
    <form method="POST" action="{{ route('guide.tours.complete', $tour) }}" data-offline-queue data-guide-tour-action-form>
        @csrf
        <button type="submit" class="btn btn-danger btn-lg w-100">
            <i class="bi bi-stop-circle me-2"></i>Avsluta tur
        </button>
    </form>
</template>

<template data-guide-tour-template="completed">
    <div class="alert alert-success mb-0 text-center">
        <i class="bi bi-check-circle me-2"></i>Tur avslutad.
    </div>
</template>
</div>

@if(!empty($tourPhotosEnabled))
<div class="page-card mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div>
            <div class="section-title mb-1">Bilder från turen</div>
            <div class="small-muted">Ladda upp bilder som hör till hela turen.</div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge-soft badge-soft-secondary">{{ $tour->photos->count() }} bilder</span>
            <a href="{{ route('guide.tours.photos.create', $tour, false) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-camera me-1"></i>Ladda upp bild
            </a>
        </div>
    </div>

    @if($tour->photos->isNotEmpty())
        <div class="row g-3">
            @foreach($tour->photos as $photo)
                <div class="col-6 col-md-4">
                    <a href="{{ $photo->url }}" target="_blank" rel="noopener" class="d-block">
                        <img src="{{ $photo->url }}" alt="{{ $photo->caption ?: $photo->original_name ?: 'Turbild' }}" class="img-fluid rounded border">
                    </a>
                    @if($photo->caption)
                        <div class="small-muted mt-1">{{ $photo->caption }}</div>
                    @endif
                    <form method="POST" action="{{ route('guide.tours.photos.destroy', [$tour, $photo], false) }}" class="mt-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                            Ta bort
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bi bi-images"></i>
            </div>
            <div class="fw-semibold">Inga bilder ännu</div>
            <div class="small-muted">När turbilder laddas upp visas de här.</div>
        </div>
    @endif
</div>
@endif

<style>
.guide-tour-quick-stats {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.45rem;
    color: #334155;
    font-size: 0.95rem;
}

.guide-tour-quick-stats-sep {
    color: #94a3b8;
}

.guide-tour-header-actions {
    display: grid;
    gap: 0.75rem;
}

.guide-tour-secondary-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.65rem;
}

.guide-tour-status-panel {
    background: #f8fafc;
}

.guide-tour-timing {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    color: #475569;
    font-size: 0.88rem;
    font-weight: 700;
}

.tour-timeline {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.75rem;
}

.tour-step {
    display: flex;
    align-items: flex-start;
    gap: 0.7rem;
    padding: 0.95rem;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
}

.tour-step-active {
    background: rgba(37, 99, 235, 0.08);
    border-color: rgba(37, 99, 235, 0.18);
}

.tour-step-dot {
    width: 12px;
    height: 12px;
    border-radius: 999px;
    margin-top: 0.25rem;
    background: #cbd5e1;
    flex: 0 0 auto;
}

.tour-step-active .tour-step-dot {
    background: var(--brand-accent);
}

@media (max-width: 900px) {
    .tour-timeline {
        grid-template-columns: 1fr;
    }
}

.booking-mobile-section {
    overflow: visible;
}

.booking-card-list {
    display: grid;
    gap: 0.9rem;
}

.booking-mobile-card {
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    background: #ffffff;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
}

.booking-mobile-summary {
    width: 100%;
    border: 0;
    background: #ffffff;
    text-align: left;
    padding: 0.8rem 0.9rem;
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr) 20px;
    gap: 0.7rem;
    align-items: center;
    cursor: pointer;
}

.booking-mobile-summary:hover {
    background: #f8fafc;
}

.booking-mobile-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #eef6ff;
    color: #2563eb;
    font-size: 1.25rem;
    flex: 0 0 auto;
}

.booking-mobile-main {
    min-width: 0;
}

.booking-mobile-title-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.65rem;
    margin-bottom: 0.45rem;
}

.booking-mobile-title {
    color: #0f172a;
    font-weight: 900;
    font-size: 0.95rem;
    line-height: 1.2;
    min-width: 0;
    word-break: break-word;
}

.booking-mobile-people {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.4rem;
    color: #475569;
    font-size: 0.84rem;
}

.booking-mobile-people-count {
    font-weight: 800;
    color: #0f172a;
}

.booking-mobile-people-breakdown {
    border-radius: 999px;
    background: #eef6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    padding: 0.12rem 0.45rem;
    font-size: 0.72rem;
    font-weight: 800;
}

.booking-mobile-people-lang {
    color: #64748b;
    font-weight: 700;
}

.booking-count-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.45rem;
}

.booking-count-pill {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    background: #f8fafc;
    padding: 0.5rem 0.35rem;
    text-align: center;
}

.booking-count-label {
    display: block;
    color: #64748b;
    font-size: 0.7rem;
    font-weight: 800;
    margin-bottom: 0.15rem;
}

.booking-count-pill strong {
    color: #0f172a;
    font-size: 1.08rem;
    line-height: 1;
}

.booking-mobile-notes {
    margin-top: 0.75rem;
    color: #475569;
    font-size: 0.86rem;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 12px;
    padding: 0.55rem 0.65rem;
}

.booking-mobile-chevron {
    color: #64748b;
    padding-top: 0.2rem;
    transition: transform 0.16s ease;
}

.booking-mobile-summary[aria-expanded="true"] .booking-mobile-chevron {
    transform: rotate(180deg);
}

.booking-mobile-edit {
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
    padding: 1rem;
}

.booking-edit-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.75rem;
}

.booking-edit-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    margin: 0;
}

.booking-edit-field span {
    color: #334155;
    font-size: 0.78rem;
    font-weight: 900;
}

.booking-edit-field input,
.booking-edit-field select {
    min-height: 46px;
    font-size: 1rem;
}

.booking-edit-status {
    grid-column: 1 / -1;
}

.booking-edit-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.6rem;
    margin-top: 0.9rem;
}

.empty-state {
    text-align: center;
    border: 1px dashed #cbd5e1;
    border-radius: 18px;
    background: #f8fafc;
    padding: 1.5rem;
}

.empty-state-icon {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #eef6ff;
    color: #2563eb;
    font-size: 1.3rem;
    margin-bottom: 0.65rem;
}

@media (max-width: 700px) {
    .booking-mobile-summary {
        grid-template-columns: 46px minmax(0, 1fr) 20px;
        gap: 0.7rem;
        padding: 0.85rem;
    }

    .booking-mobile-icon {
        width: 46px;
        height: 46px;
        border-radius: 14px;
    }

    .booking-mobile-title-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.35rem;
    }

    .booking-count-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .booking-edit-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .booking-edit-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }

    .booking-edit-actions .btn {
        width: 100%;
    }
}



.tour-category-short {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    border-radius: 999px;
    background: #eef6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    padding: 0.22rem 0.5rem;
    font-size: 0.78rem;
    font-weight: 900;
    white-space: nowrap;
    margin-left: 0.35rem;
    vertical-align: middle;
}

.guide-headcount-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.guide-start-panel,
.guide-headcount-live {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 0.85rem 1rem;
}

.guide-tour-sync-pending {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    border-radius: 999px;
    padding: 0.35rem 0.65rem;
    background: #fff7ed;
    border: 1px solid #fdba74;
    color: #9a3412;
    font-size: 0.78rem;
    font-weight: 800;
    line-height: 1.2;
    text-align: right;
}

.guide-tour-sync-pending[hidden] {
    display: none !important;
}

</style>

@php
    $guideTourUiPath = public_path('js/guide-tour-optimistic-ui.js');
    $guideTourUiVer = is_file($guideTourUiPath) ? (string) filemtime($guideTourUiPath) : '0';
@endphp
<script src="{{ asset('js/guide-tour-optimistic-ui.js') }}?v={{ $guideTourUiVer }}"></script>

<script>
    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('[data-booking-toggle]');

        if (!toggle) {
            return;
        }

        const targetId = toggle.getAttribute('data-booking-toggle');
        const target = document.getElementById(targetId);

        if (!target) {
            return;
        }

        const isHidden = target.hasAttribute('hidden');

        target.toggleAttribute('hidden', !isHidden);

        document.querySelectorAll('[data-booking-toggle="' + targetId + '"]').forEach(function (button) {
            button.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
        });
    });

    (function () {
        const bookedCount = {{ (int) $bookedCount }};
        const startInput = document.getElementById('actual_on_site_count');
        const startHidden = document.querySelector('[data-guide-start-count-hidden]');
        const startLabel = document.querySelector('[data-guide-start-count-label]');
        const diffWrap = document.querySelector('[data-headcount-diff-wrap]');
        const diffNode = document.querySelector('[data-headcount-diff]');

        function syncStartCount() {
            if (!startInput || !startHidden) {
                return;
            }

            const value = Math.max(1, parseInt(startInput.value, 10) || bookedCount);
            startInput.value = value;
            startHidden.value = value;

            if (startLabel) {
                startLabel.textContent = String(value);
            }

            const diff = value - bookedCount;

            if (diffWrap && diffNode) {
                if (diff === 0) {
                    diffWrap.setAttribute('hidden', 'hidden');
                } else {
                    diffWrap.removeAttribute('hidden');
                    diffNode.textContent = (diff > 0 ? '+' : '') + String(diff);
                }
            }
        }

        if (startInput) {
            startInput.addEventListener('input', syncStartCount);
            syncStartCount();
        }

        document.querySelectorAll('[data-headcount-adjust]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!startInput) {
                    return;
                }

                const delta = parseInt(button.getAttribute('data-headcount-adjust'), 10) || 0;
                const current = parseInt(startInput.value, 10) || bookedCount;
                startInput.value = Math.max(1, current + delta);
                syncStartCount();
            });
        });

        const liveHidden = document.querySelector('[data-guide-live-count-hidden]');
        const liveCount = document.querySelector('[data-guide-live-count]');
        const liveForm = document.querySelector('[data-guide-headcount-form]');

        function submitLiveCount(nextValue) {
            if (!liveHidden || !liveForm) {
                return;
            }

            const value = Math.max(1, nextValue);
            liveHidden.value = String(value);

            if (liveCount) {
                liveCount.textContent = String(value);
            }

            liveForm.requestSubmit();
        }

        document.querySelectorAll('[data-live-adjust]').forEach(function (button) {
            button.addEventListener('click', function () {
                const delta = parseInt(button.getAttribute('data-live-adjust'), 10) || 0;
                const current = parseInt(liveHidden ? liveHidden.value : '0', 10) || bookedCount;
                submitLiveCount(current + delta);
            });
        });
    })();
</script>
@endsection