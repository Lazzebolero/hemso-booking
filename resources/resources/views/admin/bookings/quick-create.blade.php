@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Snabbbokning</h2>
        <div class="muted">Skapa bokning snabbt till närmaste lediga tur.</div>
    </div>

    <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Tillbaka
    </a>
</div>

<div class="page-card" style="max-width: 820px;">
    <form method="POST" action="{{ route('admin.bookings.quick-store') }}" class="row g-3">
        @csrf

        <div class="col-12">
            <label class="form-label">Tur</label>
            <select name="tour_id" class="form-select" required autofocus>
                @forelse($tours as $tour)
                    @php
                        $booked = $tour->bookings->where('status', '!=', 'cancelled')->where('is_waitlist', false)->sum('total_count');
                        $available = max(0, $tour->max_participants - $booked);
                    @endphp
                    <option value="{{ $tour->id }}" @selected(old('tour_id', $preferredTourId) == $tour->id)>
                        {{ $tour->tour_date }} {{ substr($tour->start_time, 0, 5) }}
                        - {{ $tour->title }}
                        ({{ $available > 0 ? $available . ' lediga' : 'full' }})
                    </option>
                @empty
                    <option value="">Inga turer finns idag</option>
                @endforelse
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">Kontaktperson</label>
            <input type="text" name="contact_name" class="form-control" value="{{ old('contact_name') }}">
        </div>

        <div class="col-md-4">
            <label class="form-label">Telefon</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
        </div>

        <div class="col-md-4">
            <label class="form-label">E-post</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">Män</label>
            <input type="number" min="0" name="men_count" class="form-control js-count" value="{{ old('men_count', 0) }}" required>
        </div>

        <div class="col-md-3">
            <label class="form-label">Kvinnor</label>
            <input type="number" min="0" name="women_count" class="form-control js-count" value="{{ old('women_count', 0) }}" required>
        </div>

        <div class="col-md-3">
            <label class="form-label">Ungdomar</label>
            <input type="number" min="0" name="youth_count" class="form-control js-count" value="{{ old('youth_count', 0) }}" required>
        </div>

        <div class="col-md-3">
            <label class="form-label">Barn</label>
            <input type="number" min="0" name="child_count" class="form-control js-count" value="{{ old('child_count', 0) }}" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">Totalt</label>
            <input type="number" class="form-control js-total" value="0" readonly>
        </div>

        <div class="col-12">
            <label class="form-label">Språk</label>
            <div class="row g-2">
                @foreach($languages as $language)
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="languages[]" value="{{ $language->id }}" id="lang_{{ $language->id }}">
                            <label class="form-check-label" for="lang_{{ $language->id }}">
                                {{ $language->name }} ({{ strtoupper($language->code) }})
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-12">
            <label class="form-label">Anteckning</label>
            <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
        </div>

        <div class="col-12">
            <button class="btn btn-primary">
                <i class="bi bi-save me-2"></i>Boka och ny
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const countFields = document.querySelectorAll('.js-count');
    const totalField = document.querySelector('.js-total');

    function updateTotal() {
        let total = 0;
        countFields.forEach(field => {
            total += parseInt(field.value || 0, 10);
        });
        totalField.value = total;
    }

    countFields.forEach(field => field.addEventListener('input', updateTotal));
    updateTotal();
});
</script>
@endsection