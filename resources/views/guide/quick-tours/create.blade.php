@extends('layouts.guide')

@section('content')
<div class="page-card mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h2 class="page-title mb-1">Starta snabbtur</h2>
            <div class="page-subtitle">
                Skapa en extra tur direkt från guidevyn och starta den omedelbart.
            </div>
        </div>

        <a href="{{ route('guide.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<form method="POST" action="{{ route('quick-tours.store') }}" data-offline-queue>
    @csrf

    <div class="guide-quicktour-layout">
        <div class="guide-focus-card">
            <div class="section-title">Deltagare och språk</div>

            <div class="col-md-3">
    <label class="form-label">Män</label>
    <input
        type="number"
        min="0"
        name="men_count"
        class="form-control"
        value="{{ old('men_count', '') }}"
        inputmode="numeric"
    >
</div>

<div class="col-md-3">
    <label class="form-label">Kvinnor</label>
    <input
        type="number"
        min="0"
        name="women_count"
        class="form-control"
        value="{{ old('women_count', '') }}"
        inputmode="numeric"
    >
</div>

<div class="col-md-3">
    <label class="form-label">Ungdomar</label>
    <input
        type="number"
        min="0"
        name="youth_count"
        class="form-control"
        value="{{ old('youth_count', '') }}"
        inputmode="numeric"
    >
</div>

<div class="col-md-3">
    <label class="form-label">Barn</label>
    <input
        type="number"
        min="0"
        name="child_count"
        class="form-control"
        value="{{ old('child_count', '') }}"
        inputmode="numeric"
    >
</div>

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

                    <div class="form-text">Svenska är förvald, men du kan välja fler språk.</div>
                </div>

            </div>

        <div class="page-card guide-side-panel">
            
            <div class="info-item mb-3">
                <div class="small-muted mb-1">Guide</div>
                <div class="fw-semibold">{{ auth()->user()->name }}</div>
            </div>

            <div class="guide-primary-actions">
                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="bi bi-play-circle me-2"></i>Starta snabbtur
                </button>

                <a href="{{ route('guide.dashboard') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-circle me-2"></i>Avbryt
                </a>
            </div>
        </div>
    </div>
</form>

<style>
.guide-quicktour-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) 320px;
    gap: 1rem;
    align-items: start;
}

.guide-side-panel {
    align-self: start;
}

.language-chip-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 0.75rem;
}

.language-chip-option {
    position: relative;
    display: block;
    cursor: pointer;
}

.language-chip-option input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.language-chip-pill {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    min-height: 54px;
    padding: 0.9rem 1rem;
    border-radius: 16px;
    border: 1px solid #dbe3ee;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    transition: all 0.18s ease;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.language-chip-name {
    font-weight: 700;
    color: #0f172a;
}

.language-chip-code {
    font-size: 0.75rem;
    font-weight: 800;
    color: #64748b;
    background: #e2e8f0;
    border-radius: 999px;
    padding: 0.22rem 0.45rem;
}

.language-chip-option:hover .language-chip-pill {
    border-color: #93c5fd;
    background: linear-gradient(180deg, #ffffff 0%, #eff6ff 100%);
}

.language-chip-option input:checked + .language-chip-pill {
    border-color: #2563eb;
    background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.language-chip-option input:checked + .language-chip-pill .language-chip-code {
    background: rgba(37, 99, 235, 0.14);
    color: #1d4ed8;
}

.quicktour-textarea {
    min-height: 180px !important;
}

@media (max-width: 1100px) {
    .guide-quicktour-layout {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection