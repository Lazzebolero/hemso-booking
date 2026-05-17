@extends('layouts.guide')

@section('content')
@php
    $bookings = collect($tour->bookings ?? []);
    $activeBookings = $bookings->whereNotIn('status', ['cancelled']);

    $bookingCount = $activeBookings->count();
    $bookedCount = $activeBookings->sum('total_count');

    $maxParticipants = $tour->max_participants ?? 0;
    $available = max(0, $maxParticipants - $bookedCount);

    $percent = $maxParticipants > 0
        ? min(100, round(($bookedCount / $maxParticipants) * 100))
        : 0;

    $barColor = $available <= 0
        ? 'var(--brand-danger)'
        : ($available <= 5 ? 'var(--brand-warning)' : 'var(--brand-success)');

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
    $tourCategoryShort = "M{$tourMen} K{$tourWomen} U{$tourYouth} B{$tourChildren}";
@endphp


<div class="page-card mb-4">
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

        <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
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
</div>

<div class="stats-grid mb-4">
    <div class="stats-card">
        <div class="stats-label">Bokningar</div>
        <div class="stats-value">{{ $bookingCount }}</div>
        <div class="stats-subtext">Aktiva grupper på turen.</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Bokade personer</div>
        <div class="stats-value">{{ $bookedCount }} <span class="tour-category-short">{{ $tourCategoryShort }}</span></div>
        <div class="stats-subtext">Totalt antal deltagare just nu.</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Lediga platser</div>
        <div class="stats-value">{{ $available }}</div>
        <div class="stats-subtext">
            @if($maxParticipants > 0)
                Av {{ $maxParticipants }} maxplatser
            @else
                Ingen maxgräns satt
            @endif
        </div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Beläggning</div>
        <div class="stats-value">{{ $percent }}%</div>
        <div class="stats-subtext">Nuvarande fyllnadsgrad.</div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="page-card">
            <div class="section-title">Turstatus</div>

            <div class="tour-timeline">
                <div class="tour-step {{ in_array($status, ['planned', 'started', 'completed']) ? 'tour-step-active' : '' }}">
                    <div class="tour-step-dot"></div>
                    <div>
                        <div class="fw-semibold">Planerad</div>
                        <div class="small-muted">
                            {{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}
                        </div>
                    </div>
                </div>

                <div class="tour-step {{ in_array($status, ['started', 'completed']) ? 'tour-step-active' : '' }}">
                    <div class="tour-step-dot"></div>
                    <div>
                        <div class="fw-semibold">Startad</div>
                        <div class="small-muted">{{ $startedAtLabel ?? '-' }}</div>
                    </div>
                </div>

                <div class="tour-step {{ $status === 'completed' ? 'tour-step-active' : '' }}">
                    <div class="tour-step-dot"></div>
                    <div>
                        <div class="fw-semibold">Avslutad</div>
                        <div class="small-muted">{{ $endedAtLabel ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="progress-modern mt-3 mb-3">
                <div style="width: {{ $percent }}%; background: {{ $barColor }};"></div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="info-item">
                        <div class="small-muted mb-1">Planerad turtid</div>
                        <div class="fw-semibold">
                            {{ $plannedDurationMinutes !== null && $plannedDurationMinutes > 0 ? $plannedDurationMinutes . ' min' : '-' }}
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="info-item">
                        <div class="small-muted mb-1">Beräknas klar</div>
                        <div class="fw-semibold">{{ $estimatedEndTime ?? '-' }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="info-item">
                        <div class="small-muted mb-1">Tid kvar</div>
                        <div class="fw-semibold">{{ $remainingToEnd ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="page-card">
            <div class="section-title">Åtgärder</div>

            <div class="guide-primary-actions">
                @if($status === 'planned')
                    <form method="POST" action="{{ route('guide.tours.start', $tour) }}" data-offline-queue>
                        @csrf
                        <button class="btn btn-success btn-lg w-100">
                            <i class="bi bi-play-circle me-2"></i>Starta tur
                        </button>
                    </form>
                @endif

                @if($status === 'started')
                    <form method="POST" action="{{ route('guide.tours.complete', $tour) }}" data-offline-queue>
                        @csrf
                        <button class="btn btn-danger btn-lg w-100">
                            <i class="bi bi-stop-circle me-2"></i>Avsluta tur
                        </button>
                    </form>
                @endif

                <a href="{{ route('guide.reports.create') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-exclamation-triangle me-2"></i>Felrapport
                </a>

                <a href="{{ route('guide.dashboard') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-left me-2"></i>Tillbaka
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-card mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div>
            <div class="section-title mb-1">Bilder från turen</div>
            <div class="small-muted">Ladda upp bilder som hör till hela turen, till exempel företags- eller gruppbilder.</div>
        </div>

        <span class="badge-soft badge-soft-secondary">
            {{ $tour->photos->count() }} bilder
        </span>
    </div>

    <form method="POST" action="{{ route('guide.tours.photos.store', $tour, false) }}" enctype="multipart/form-data" class="tour-photo-upload" data-offline-ignore>
        @csrf

        <div class="tour-photo-upload-grid">
            <label class="tour-photo-upload-field">
                <span>Ta eller välj bild</span>
                <input
                    type="file"
                    name="photo_camera"
                    class="form-control"
                    accept="image/*"
                >
                <small class="small-muted">Om kameran ger minnesfel: ta bilden i kameraappen först och välj den sedan från bilder.</small>
            </label>

            <label class="tour-photo-upload-field">
                <span>Välj från bilder</span>
                <input
                    type="file"
                    name="photo_library"
                    class="form-control"
                    accept="image/jpeg,image/png,image/gif,image/webp,image/heic,image/heif,.heic,.heif"
                >
            </label>

            <label class="tour-photo-upload-field">
                <span>Bildtext (valfritt)</span>
                <input
                    type="text"
                    name="caption"
                    class="form-control"
                    maxlength="255"
                    placeholder="Ex. Företagsgrupp vid kanonen"
                >
            </label>

            <div class="tour-photo-upload-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-camera me-2"></i>Ladda upp bild
                </button>
            </div>
        </div>

        @error('photo')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
        @error('photo_camera')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
        @error('photo_library')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
        @error('caption')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror

        <div class="small-muted mt-2">
            Välj en bild innan du laddar upp.
        </div>
    </form>

    @if($tour->photos->isNotEmpty())
        <div class="tour-photo-grid mt-3">
            @foreach($tour->photos as $photo)
                <article class="tour-photo-card">
                    <a href="{{ route('guide.tours.photos.show', ['tour' => $tour, 'tourPhoto' => $photo], false) }}" target="_blank" rel="noopener">
                        <img src="{{ route('guide.tours.photos.show', ['tour' => $tour, 'tourPhoto' => $photo], false) }}" alt="{{ $photo->caption ?: 'Turbild' }}">
                    </a>

                    <div class="tour-photo-body">
                        @if($photo->caption)
                            <div class="fw-semibold">{{ $photo->caption }}</div>
                        @endif

                        <div class="small-muted">
                            Uppladdad {{ $photo->created_at?->format('Y-m-d H:i') }}
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>

<div class="page-card booking-mobile-section">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <div>
            <div class="section-title mb-1">Bokningar</div>
            <div class="small-muted">Översikt per grupp. Tryck på en grupp för att ändra antal.</div>
        </div>

        <div class="badge-soft badge-soft-secondary">
            <i class="bi bi-people"></i>
            {{ $bookedCount }} bokade
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

                        <div class="booking-mobile-meta">
                            @if($bookingLanguages)
                                <span><i class="bi bi-translate"></i>{{ $bookingLanguages }}</span>
                            @endif

                            @if(!empty($booking->contact_name) && $booking->contact_name !== $bookingTitle)
                                <span><i class="bi bi-person"></i>{{ $booking->contact_name }}</span>
                            @endif

                            <span><i class="bi bi-calculator"></i>{{ $bookingTotal }} totalt</span>
                        </div>

                        <div class="booking-count-grid">
                            <div class="booking-count-pill">
                                <span class="booking-count-label">Män</span>
                                <strong>{{ (int) $booking->men_count }}</strong>
                            </div>

                            <div class="booking-count-pill">
                                <span class="booking-count-label">Kvinnor</span>
                                <strong>{{ (int) $booking->women_count }}</strong>
                            </div>

                            <div class="booking-count-pill">
                                <span class="booking-count-label">Ungdomar</span>
                                <strong>{{ (int) $booking->youth_count }}</strong>
                            </div>

                            <div class="booking-count-pill">
                                <span class="booking-count-label">Barn</span>
                                <strong>{{ (int) $booking->child_count }}</strong>
                            </div>
                        </div>

                        @if(!empty($booking->notes))
                            <div class="booking-mobile-notes">
                                {{ \Illuminate\Support\Str::limit($booking->notes, 110) }}
                            </div>
                        @endif
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

<style>
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
    padding: 1rem;
    display: grid;
    grid-template-columns: 52px minmax(0, 1fr) 24px;
    gap: 0.85rem;
    align-items: flex-start;
    cursor: pointer;
}

.booking-mobile-summary:hover {
    background: #f8fafc;
}

.booking-mobile-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
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
    font-size: 1rem;
    line-height: 1.2;
    min-width: 0;
    word-break: break-word;
}

.booking-mobile-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    color: #64748b;
    font-size: 0.82rem;
    margin-bottom: 0.75rem;
}

.booking-mobile-meta span {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
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

.tour-photo-upload {
    border: 1px dashed #cbd5e1;
    border-radius: 18px;
    background: #f8fafc;
    padding: 1rem;
}

.tour-photo-upload-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
    gap: 0.85rem;
    align-items: end;
}

.tour-photo-upload-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    margin: 0;
}

.tour-photo-upload-field span {
    color: #334155;
    font-size: 0.78rem;
    font-weight: 900;
}

.tour-photo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 0.85rem;
}

.tour-photo-card {
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: #fff;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
}

.tour-photo-card img {
    width: 100%;
    height: 150px;
    display: block;
    object-fit: cover;
    background: #f1f5f9;
}

.tour-photo-body {
    padding: 0.75rem;
}

@media (max-width: 700px) {
    .tour-photo-upload-grid {
        grid-template-columns: 1fr;
    }

    .tour-photo-upload-actions .btn {
        width: 100%;
    }

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

</style>

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

    setTimeout(function () {
        if (navigator.onLine) {
            window.location.reload();
        }
    }, 30000);
</script>
@endsection