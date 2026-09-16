@props([
    'booking' => null,
    'showQuickTotal' => false,
    'compact' => false,
    'focusFirstField' => null,
])

@php
    $booking = $booking ?? new \App\Models\Booking();
@endphp

@if($showQuickTotal)
    <div class="{{ $compact ? 'col-12' : 'col-md-6' }}">
        <label class="form-label">Antal personer (totalt)</label>
        <input
            type="number"
            min="1"
            name="participant_count"
            class="form-control js-participant-total"
            value="{{ old('participant_count') }}"
            inputmode="numeric"
        >
        <div class="form-text">Räcker med total — fördelning m/k/u/b kan göras senare.</div>
    </div>
@endif

<div class="{{ $compact ? 'col-6 col-md-3' : 'col-md-3' }}">
    <label class="form-label">Män</label>
    <input type="number" min="0" name="men_count" class="form-control js-participant-field{{ $focusFirstField === 'men' ? ' js-focus-first' : '' }}" value="{{ old('men_count', $booking->men_count ?? 0) }}">
</div>

<div class="{{ $compact ? 'col-6 col-md-3' : 'col-md-3' }}">
    <label class="form-label">Kvinnor</label>
    <input type="number" min="0" name="women_count" class="form-control js-participant-field" value="{{ old('women_count', $booking->women_count ?? 0) }}">
</div>

<div class="{{ $compact ? 'col-6 col-md-3' : 'col-md-3' }}">
    <label class="form-label">Ungdomar</label>
    <input type="number" min="0" name="youth_count" class="form-control js-participant-field" value="{{ old('youth_count', $booking->youth_count ?? 0) }}">
</div>

<div class="{{ $compact ? 'col-6 col-md-3' : 'col-md-3' }}">
    <label class="form-label">Barn</label>
    <input type="number" min="0" name="child_count" class="form-control js-participant-field" value="{{ old('child_count', $booking->child_count ?? 0) }}">
</div>

<div class="{{ $compact ? 'col-12 col-md-6' : 'col-md-3' }}">
    <label class="form-label">Ospecificerade</label>
    <input type="number" min="0" name="unspecified_count" class="form-control js-participant-field{{ $focusFirstField === 'unspecified' ? ' js-focus-first' : '' }}" value="{{ old('unspecified_count', $booking->unspecified_count ?? 0) }}">
    <div class="form-text">Använd när ni vet totalen men inte fördelningen.</div>
</div>
