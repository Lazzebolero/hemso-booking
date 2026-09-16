@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Extra tur</h2>
        <div class="page-subtitle">
            Skapa en namngiven tur med datum, tid och bokning — till exempel utanför ordinarie säsong.
        </div>
    </div>

    <div class="page-actions">
        <a href="{{ route($prefix . '.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<form method="POST" action="{{ route($prefix . '.extra-tours.store') }}">
    @csrf

    <div class="form-layout">
        <div class="page-card">
            <div class="section-title">Tur</div>

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Namn</label>
                    <input
                        type="text"
                        name="title"
                        class="form-control"
                        value="{{ old('title') }}"
                        required
                        placeholder="T.ex. Privatvisning Andersson"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Turtyp</label>
                    <select name="tour_type_id" class="form-select" required>
                        <option value="">Välj turtyp</option>
                        @foreach($tourTypes as $tourType)
                            <option
                                value="{{ $tourType->id }}"
                                @selected((string) old('tour_type_id', $defaultTourTypeId) === (string) $tourType->id)
                            >
                                {{ $tourType->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Datum</label>
                    <input
                        type="date"
                        name="tour_date"
                        class="form-control"
                        value="{{ old('tour_date') }}"
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Starttid</label>
                    <input
                        type="time"
                        name="start_time"
                        class="form-control"
                        value="{{ old('start_time', '10:00') }}"
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Sluttid</label>
                    <input
                        type="time"
                        name="end_time"
                        class="form-control"
                        value="{{ old('end_time') }}"
                    >
                    <div class="form-text">Lämna tomt för standardlängd från turtypen.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Guide</label>
                    <select name="guide_id" class="form-select">
                        <option value="">Ingen guide ännu</option>
                        @foreach($guides as $guide)
                            <option value="{{ $guide->id }}" @selected((string) old('guide_id') === (string) $guide->id)>
                                {{ $guide->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Kontaktperson</label>
                    <input
                        type="text"
                        name="contact_name"
                        class="form-control"
                        value="{{ old('contact_name') }}"
                        placeholder="Valfritt — annars samma som turnamn"
                    >
                </div>
            </div>
        </div>

        <div class="page-card mt-3">
            <div class="section-title">Deltagare och språk</div>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Ospecificerade</label>
                    <input
                        type="number"
                        min="0"
                        name="unspecified_count"
                        class="form-control"
                        value="{{ old('unspecified_count', 1) }}"
                        required
                    >
                    <div class="form-text">Vanligast när ni bara vet totalen.</div>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Män</label>
                    <input type="number" min="0" name="men_count" class="form-control" value="{{ old('men_count', 0) }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Kvinnor</label>
                    <input type="number" min="0" name="women_count" class="form-control" value="{{ old('women_count', 0) }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Ungdomar</label>
                    <input type="number" min="0" name="youth_count" class="form-control" value="{{ old('youth_count', 0) }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Barn</label>
                    <input type="number" min="0" name="child_count" class="form-control" value="{{ old('child_count', 0) }}">
                </div>

                @include('partials.bookings.meal-select', [
                    'defaultIncludesMeal' => false,
                    'columnClass' => 'col-md-4',
                ])

                <div class="col-12">
                    <label class="form-label">Språk</label>
                    @php
                        $selectedLanguages = collect(old('language_ids', $defaultLanguageIds ?? []))
                            ->map(fn ($id) => (string) $id)
                            ->all();
                    @endphp

                    <div class="language-chip-grid">
                        @foreach(($languages ?? collect()) as $language)
                            <label class="language-chip-option">
                                <input
                                    type="checkbox"
                                    name="language_ids[]"
                                    value="{{ $language->id }}"
                                    @checked(in_array((string) $language->id, $selectedLanguages, true))
                                >
                                <span class="language-chip-pill">
                                    <span class="language-chip-name">{{ $language->name }}</span>
                                    @if(!empty($language->code))
                                        <span class="language-chip-code">{{ strtoupper($language->code) }}</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Anteckning</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="form-side-box mt-3">
            <div class="section-title">Spara</div>
            <p class="small-muted mb-3">
                Skapar en vanlig planerad tur och en bekräftad bokning. Hanteras i schema och statistik som övriga turer.
            </p>
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-plus-circle me-2"></i>Skapa tur och bokning
            </button>
        </div>
    </div>
</form>

<style>
.language-chip-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.55rem;
}
.language-chip-option {
    margin: 0;
    cursor: pointer;
}
.language-chip-option input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.language-chip-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.45rem 0.75rem;
    border-radius: 999px;
    border: 1px solid var(--brand-line-soft);
    background: #f8fafc;
    font-weight: 700;
    font-size: 0.9rem;
}
.language-chip-option input:checked + .language-chip-pill {
    border-color: var(--brand-primary);
    background: rgba(99, 102, 241, 0.1);
}
.language-chip-code {
    color: #64748b;
    font-size: 0.78rem;
}
.form-side-box {
    background: #f8fafc;
    border: 1px solid var(--brand-line-soft);
    border-radius: 12px;
    padding: 0.95rem;
}
</style>
@endsection
