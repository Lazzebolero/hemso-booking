@extends('layouts.app')

@section('content')
<div class="guide-mobile-header mb-4">
    <div>
        <h2 class="mb-1">{{ $tour->title }}</h2>
        <div class="muted">
            <div><i class="bi bi-calendar-event me-1"></i>{{ $tour->tour_date }}</div>
            <div><i class="bi bi-clock me-1"></i>{{ $tour->start_time }}{{ $tour->end_time ? ' - ' . $tour->end_time : '' }}</div>
        </div>
    </div>
    <a href="{{ route('guide.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-2"></i>Tillbaka
    </a>
</div>

@php
    $barColor = $availableSpots <= 0 ? 'var(--brand-danger)' : ($availableSpots <= 5 ? 'var(--brand-warning)' : 'var(--brand-success)');
@endphp

<div class="guide-mobile-list mb-4">
    <div class="guide-tour-card">
        <div class="guide-tour-stats">
            <div class="guide-stat-box">
                <div class="guide-stat-label">Bokningar</div>
                <div class="guide-stat-value">{{ $bookingCount }}</div>
            </div>
            <div class="guide-stat-box">
                <div class="guide-stat-label">Bokade</div>
                <div class="guide-stat-value">{{ $bookedCount }}</div>
            </div>
            <div class="guide-stat-box">
                <div class="guide-stat-label">Lediga</div>
                <div class="guide-stat-value">{{ $availableSpots }}</div>
            </div>
            <div class="guide-stat-box">
                <div class="guide-stat-label">Status</div>
                <div class="guide-stat-value guide-stat-small"><x-status-chip :status="$tour->status" /></div>
            </div>
        </div>

        <div class="d-flex justify-content-between small mb-2">
            <span>{{ $bookedCount }}/{{ $tour->max_participants }}</span>
            <span class="muted">{{ $occupancyPercent }}%</span>
        </div>
        <div class="progress-modern mb-3">
            <div style="width: {{ min(100, $occupancyPercent) }}%; background: {{ $barColor }};"></div>
        </div>

        <div class="guide-mobile-actions">
            @if($tour->status === 'planned')
                <form method="POST" action="{{ route('guide.tours.start', $tour) }}" class="w-100">
                    @csrf
                    <button class="btn btn-success w-100">
                        <i class="bi bi-play-circle me-2"></i>Starta tur
                    </button>
                </form>
            @endif

            @if($tour->status === 'started')
                <form method="POST" action="{{ route('guide.tours.complete', $tour) }}" class="w-100">
                    @csrf
                    <button class="btn btn-danger w-100">
                        <i class="bi bi-stop-circle me-2"></i>Avsluta tur
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="guide-mobile-list">
    @forelse($tour->bookings as $booking)
        <div class="guide-tour-card">
            <div class="guide-tour-top">
                <div>
                    <div class="guide-tour-title">{{ $booking->booking_name }}</div>
                    <div class="guide-tour-meta">
                        <div>{{ $booking->contact_name ?? '-' }}</div>
                        @if($booking->phone)<div>{{ $booking->phone }}</div>@endif
                        @if($booking->email)<div>{{ $booking->email }}</div>@endif
                    </div>
                </div>
                <x-status-chip :status="$booking->status === 'completed' ? 'completed' : ($booking->status === 'preliminary' ? 'warning' : ($booking->status === 'cancelled' ? 'cancelled' : 'success'))" />
            </div>

            <form method="POST" action="{{ route('guide.bookings.update-participants', $booking) }}">
                @csrf
                @method('PATCH')

                <div class="guide-tour-stats">
                    <div class="guide-stat-box">
                        <div class="guide-stat-label">Män</div>
                        <input type="number" min="0" name="men_count" value="{{ $booking->men_count }}" class="form-control form-control-sm js-count-field">
                    </div>
                    <div class="guide-stat-box">
                        <div class="guide-stat-label">Kvinnor</div>
                        <input type="number" min="0" name="women_count" value="{{ $booking->women_count }}" class="form-control form-control-sm js-count-field">
                    </div>
                    <div class="guide-stat-box">
                        <div class="guide-stat-label">Ungdomar</div>
                        <input type="number" min="0" name="youth_count" value="{{ $booking->youth_count }}" class="form-control form-control-sm js-count-field">
                    </div>
                    <div class="guide-stat-box">
                        <div class="guide-stat-label">Barn</div>
                        <input type="number" min="0" name="child_count" value="{{ $booking->child_count }}" class="form-control form-control-sm js-count-field">
                    </div>
                </div>

                <div class="row g-3 align-items-end">
                    <div class="col-6">
                        <label class="form-label small muted">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            @foreach(['preliminary', 'confirmed', 'cancelled', 'completed'] as $status)
                                <option value="{{ $status }}" @selected($booking->status === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small muted">Total</label>
                        <input type="number" value="{{ $booking->total_count }}" class="form-control form-control-sm js-total-preview" readonly>
                    </div>
                </div>

                <button class="btn btn-primary w-100 mt-3" type="submit">
                    <i class="bi bi-save me-2"></i>Spara grupp
                </button>
            </form>
        </div>
    @empty
        <div class="page-card">
            <div class="muted">Inga bokade grupper på denna tur.</div>
        </div>
    @endforelse
</div>

<style>
.guide-mobile-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    flex-wrap: wrap;
}
.guide-mobile-list {
    display: grid;
    gap: 1rem;
}
.guide-tour-card {
    background: rgba(255,255,255,0.96);
    border-radius: 22px;
    box-shadow: var(--shadow-soft);
    border: 1px solid rgba(255,255,255,0.75);
    padding: 1rem;
}
.guide-tour-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.8rem;
    margin-bottom: 1rem;
}
.guide-tour-title {
    font-size: 1.05rem;
    font-weight: 700;
    margin-bottom: 0.35rem;
}
.guide-tour-meta {
    display: grid;
    gap: 0.2rem;
    color: var(--text-soft);
    font-size: 0.92rem;
}
.guide-tour-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}
.guide-stat-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 0.8rem;
}
.guide-stat-label {
    font-size: 0.82rem;
    color: var(--text-soft);
    margin-bottom: 0.25rem;
}
.guide-stat-value {
    font-size: 1rem;
    font-weight: 700;
}
.guide-stat-small {
    font-size: 0.88rem;
}
.guide-mobile-actions {
    display: grid;
    gap: 0.75rem;
    margin-top: 1rem;
}
@media (max-width: 576px) {
    .content-area {
        padding: 1rem 0.85rem 5.5rem;
    }
    .guide-tour-card {
        padding: 0.9rem;
        border-radius: 18px;
    }
    .guide-tour-top {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.guide-tour-card').forEach(function(card) {
        const fields = card.querySelectorAll('.js-count-field');
        const preview = card.querySelector('.js-total-preview');
        if (!fields.length || !preview) return;

        function updateTotal() {
            let total = 0;
            fields.forEach(function(field) {
                total += parseInt(field.value || 0, 10);
            });
            preview.value = total;
        }

        fields.forEach(function(field) {
            field.addEventListener('input', updateTotal);
        });

        updateTotal();
    });
});
</script>
@endsection
