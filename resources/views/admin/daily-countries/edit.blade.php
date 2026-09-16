@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
    $selected = collect($selectedCountryIds ?? []);
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Dagens länder</h2>
        <div class="page-subtitle">
            Snabb notering av vilka länder som funnits på platsen — utan koppling till enskilda bokningar.
        </div>
    </div>

    <div class="page-actions">
        <a href="{{ route($prefix . '.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Till dashboard
        </a>
    </div>
</div>

@include('partials.ui.flash-messages')

<div class="page-card">
    <form method="POST" action="{{ route($prefix . '.daily-countries.update') }}" class="js-daily-countries-form">
        @csrf
        @method('PUT')

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label" for="daily_country_date">Datum</label>
                <input
                    type="date"
                    id="daily_country_date"
                    name="date"
                    class="form-control"
                    value="{{ old('date', $date->toDateString()) }}"
                    required
                >
            </div>

            <div class="col-md-8 d-flex align-items-end">
                <div class="small-muted">
                    @if($log?->updatedBy)
                        Senast sparad av {{ $log->updatedBy->name }}
                        @if($log->updated_at)
                            · {{ $log->updated_at->format('Y-m-d H:i') }}
                        @endif
                    @else
                        Inget sparat för valt datum ännu.
                    @endif
                </div>
            </div>
        </div>

        <div class="section-title mb-2">Snabbval</div>
        <div class="country-grid mb-3">
            @foreach($quickPickCountries as $country)
                <label class="country-tile js-daily-country-tile{{ $selected->contains($country->id) ? ' is-selected' : '' }}">
                    <input
                        type="checkbox"
                        class="d-none js-daily-country-checkbox"
                        name="country_ids[]"
                        value="{{ $country->id }}"
                        @checked($selected->contains($country->id))
                    >
                    <img src="{{ $country->flagUrl() }}" alt="" class="country-flag-img" loading="lazy">
                    <span class="fw-semibold">{{ $country->name }}</span>
                </label>
            @endforeach
        </div>

        <div class="section-title mb-2">Övriga länder</div>
        <div class="country-grid country-grid-other mb-3">
            @forelse($otherCountries as $country)
                <label class="country-tile country-tile-sm js-daily-country-tile{{ $selected->contains($country->id) ? ' is-selected' : '' }}">
                    <input
                        type="checkbox"
                        class="d-none js-daily-country-checkbox"
                        name="country_ids[]"
                        value="{{ $country->id }}"
                        @checked($selected->contains($country->id))
                    >
                    <img src="{{ $country->flagUrl() }}" alt="" class="country-flag-img" loading="lazy">
                    <span class="fw-semibold">{{ $country->name }}</span>
                </label>
            @empty
                <div class="small-muted">Inga övriga länder i listan.</div>
            @endforelse
        </div>

        <div class="mb-3">
            <label class="form-label" for="country_proposed_name">Finns inte i listan?</label>
            <input
                type="text"
                id="country_proposed_name"
                name="country_proposed_name"
                class="form-control"
                value="{{ old('country_proposed_name') }}"
                placeholder="Skriv land här, t.ex. Island"
            >
            <div class="form-text">Sparas som föreslaget land och kan godkännas under Länder.</div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="daily_country_notes">Anteckning (valfritt)</label>
            <textarea
                id="daily_country_notes"
                name="notes"
                class="form-control"
                rows="2"
                placeholder="T.ex. många tyskar på förmiddagen"
            >{{ old('notes', $log?->notes) }}</textarea>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-2"></i>Spara dagens länder
            </button>
            <a href="{{ route($prefix . '.daily-countries.edit', ['date' => now()->toDateString()]) }}" class="btn btn-outline-secondary">
                Idag
            </a>
        </div>
    </form>
</div>

<style>
.country-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.65rem;
}
.country-grid-other {
    max-height: 320px;
    overflow-y: auto;
    padding-right: 0.25rem;
}
.country-tile {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.35rem;
    background: #f8fafc;
    border: 1px solid var(--brand-line-soft);
    border-radius: 12px;
    padding: 0.75rem 0.85rem;
    min-height: 52px;
    cursor: pointer;
    text-align: left;
    width: 100%;
}
.country-tile-sm {
    min-height: 44px;
    padding: 0.55rem 0.7rem;
}
.country-tile.is-selected {
    border-color: var(--brand-primary);
    background: rgba(99,102,241,0.08);
}
.country-flag-img {
    width: 28px;
    height: 20px;
    object-fit: cover;
    border-radius: 3px;
    border: 1px solid rgba(0, 0, 0, 0.08);
}
@media (max-width: 900px) {
    .country-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 600px) {
    .country-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('daily_country_date');
    const tiles = Array.from(document.querySelectorAll('.js-daily-country-tile'));

    tiles.forEach(tile => {
        const checkbox = tile.querySelector('.js-daily-country-checkbox');
        if (!checkbox) {
            return;
        }

        tile.addEventListener('click', function (event) {
            if (event.target === checkbox) {
                return;
            }

            event.preventDefault();
            checkbox.checked = !checkbox.checked;
            tile.classList.toggle('is-selected', checkbox.checked);
        });

        checkbox.addEventListener('change', function () {
            tile.classList.toggle('is-selected', checkbox.checked);
        });
    });

    if (dateInput) {
        dateInput.addEventListener('change', function () {
            if (!dateInput.value) {
                return;
            }

            const url = new URL(@json(route($prefix . '.daily-countries.edit')), window.location.origin);
            url.searchParams.set('date', dateInput.value);
            window.location.href = url.toString();
        });
    }
});
</script>
@endsection
