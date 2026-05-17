@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">{{ $tour->title }}</h2>
        <div class="page-subtitle">
            {{ $tour->tour_date ? \Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d') : '-' }}
        </div>
    </div>

    <div class="page-actions">
        <a href="{{ route($prefix . '.tours.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>

        <a href="{{ route($prefix . '.bookings.create', ['tour_id' => $tour->id]) }}" class="btn btn-primary">
            <i class="bi bi-journal-plus me-2"></i>Boka
        </a>

        @if($tour->status !== 'completed')
            <a href="{{ route($prefix . '.tours.edit', $tour) }}" class="btn btn-outline-secondary">
                <i class="bi bi-pencil me-2"></i>Redigera
            </a>
        @endif

        @if($tour->status === 'planned')
            <form method="POST" action="{{ route($prefix . '.tours.start', $tour) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="bi bi-play-fill me-2"></i>Starta tur
                </button>
            </form>
        @endif

        @if($tour->status === 'started')
            <form method="POST" action="{{ route($prefix . '.tours.complete', $tour) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-stop-fill me-2"></i>Avsluta tur
                </button>
            </form>
        @endif
    </div>
</div>

@php
    $languageCodes = $tour->bookings
        ->flatMap(fn ($booking) => $booking->languages->pluck('code'))
        ->filter()
        ->map(fn ($code) => strtoupper($code))
        ->unique()
        ->values();

    $activeBookings = $tour->bookings
        ->whereNotIn('status', ['cancelled'])
        ->where('is_waitlist', false);

    $totalPeople = $activeBookings->sum('total_count');
    $totalBookings = $activeBookings->count();

    $occupancy = ($tour->max_participants ?? 0) > 0
        ? round(($totalPeople / $tour->max_participants) * 100)
        : 0;

    $availableSpots = max(0, ($tour->max_participants ?? 0) - $totalPeople);
@endphp

<div class="stats-grid mb-4">
    <div class="stats-card">
        <div class="stats-label">Bokade personer</div>
        <div class="stats-value">{{ $totalPeople }}</div>
        <div class="stats-subtext">Totalt bokade på turen</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Antal bokningar</div>
        <div class="stats-value">{{ $totalBookings }}</div>
        <div class="stats-subtext">Aktiva bokningar</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Lediga platser</div>
        <div class="stats-value">{{ $availableSpots }}</div>
        <div class="stats-subtext">Kvar av {{ $tour->max_participants }}</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Beläggning</div>
        <div class="stats-value">{{ $occupancy }}%</div>
        <div class="stats-subtext">Aktuell fyllnadsgrad</div>
    </div>
</div>

<div class="admin-grid-2 mb-4">
    <div class="page-card">
        <div class="section-title">Turinformation</div>

        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Turtyp</div>
                <div class="info-value">{{ $tour->tourType?->name ?? '-' }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Guide</div>
                <div class="info-value">{{ $tour->guide?->name ?? 'Ej tilldelad' }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Språk</div>
                <div class="info-value">
                    @if($languageCodes->isEmpty())
                        -
                    @else
                        {{ $languageCodes->implode(' + ') }}
                    @endif
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Datum</div>
                <div class="info-value">{{ $tour->tour_date ? \Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d') : '-' }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Starttid</div>
                <div class="info-value">{{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Sluttid</div>
                <div class="info-value">{{ !empty($tour->end_time) ? substr($tour->end_time, 0, 5) : '-' }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Status</div>
                <div class="info-value">{{ ucfirst($tour->status ?? '-') }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Max deltagare</div>
                <div class="info-value">{{ $tour->max_participants ?? '-' }}</div>
            </div>
        </div>

        @if(!empty($tour->description))
            <div class="mt-3">
                <div class="info-label mb-1">Beskrivning</div>
                <div class="small-muted">{!! nl2br(e($tour->description)) !!}</div>
            </div>
        @endif
    </div>
</div>

<div class="page-card">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <div class="section-title mb-1">Bilder från turen</div>
            <div class="small-muted">Bilder uppladdade av guide, kopplade till hela turen.</div>
        </div>

        <span class="badge-soft badge-soft-secondary">
            {{ $tour->photos->count() }} bilder
        </span>
    </div>

    @if($tour->photos->isEmpty())
        <div class="empty-state">
            Inga bilder uppladdade för denna tur.
        </div>
    @else
        <div class="tour-photo-grid">
            @foreach($tour->photos as $photo)
                <article class="tour-photo-card">
                    <a href="{{ route('admin.tours.photos.show', ['tour' => $tour, 'tourPhoto' => $photo], false) }}" target="_blank" rel="noopener">
                        <img src="{{ route('admin.tours.photos.show', ['tour' => $tour, 'tourPhoto' => $photo], false) }}" alt="{{ $photo->caption ?: 'Turbild' }}">
                    </a>

                    <div class="tour-photo-body">
                        @if($photo->caption)
                            <div class="fw-semibold">{{ $photo->caption }}</div>
                        @endif

                        <div class="small-muted">
                            Uppladdad {{ $photo->created_at?->format('Y-m-d H:i') }}
                            @if($photo->uploader)
                                av {{ $photo->uploader->name }}
                            @endif
                        </div>

                        <div class="toolbar-inline mt-2">
                            <a href="{{ route('admin.tours.photos.download', ['tour' => $tour, 'tourPhoto' => $photo], false) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-download me-1"></i>Ladda ner original
                            </a>

                            <form method="POST" action="{{ route('admin.tours.photos.destroy', ['tour' => $tour, 'tourPhoto' => $photo], false) }}" onsubmit="return confirm('Ta bort bilden från turen?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash me-1"></i>Ta bort
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>

<style>
.tour-photo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1rem;
}

.tour-photo-card {
    border: 1px solid var(--brand-line-soft);
    border-radius: 16px;
    background: #fff;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
}

.tour-photo-card img {
    width: 100%;
    height: 180px;
    display: block;
    object-fit: cover;
    background: #f1f5f9;
}

.tour-photo-body {
    padding: 0.85rem;
}
</style>
@endsection