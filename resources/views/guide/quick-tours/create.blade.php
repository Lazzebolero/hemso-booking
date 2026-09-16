@extends('layouts.guide')

@section('content')
<div class="page-card mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h2 class="page-title mb-1">Starta snabbtur</h2>
            <div class="page-subtitle">
                Ange antal personer och starta direkt — fördelning m/k/u/b kan göras senare.
            </div>
        </div>

        <a href="{{ route('guide.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

@php
    $quickTourBlocked = !($quickTourAssessment['can_start'] ?? true);
@endphp

@if($errors->has('quick_tour'))
    <div class="alert alert-danger mb-3">{{ $errors->first('quick_tour') }}</div>
@endif

@if($quickTourBlocked)
    <div class="alert alert-warning mb-3">
        <div>{{ $quickTourAssessment['message'] ?? 'Snabbtur kan inte startas just nu.' }}</div>
        @if(!empty($quickTourAssessment['tour_id']))
            <div class="mt-2">
                <a href="{{ route('guide.tours.show', $quickTourAssessment['tour_id']) }}" class="btn btn-sm btn-primary">
                    Öppna planerad tur
                </a>
            </div>
        @endif
    </div>
@endif

<form method="POST" action="{{ route('quick-tours.store') }}" data-offline-queue id="guide-quicktour-form"@if($quickTourBlocked) class="pe-none opacity-50"@endif>
    @csrf

    <div class="guide-quicktour-layout">
        <div class="guide-focus-card">
            <div class="section-title">Deltagare</div>

            <div class="guide-quick-count-wrap mb-3">
                <label class="form-label" for="participant_count">Antal personer</label>
                <input
                    type="number"
                    min="1"
                    id="participant_count"
                    name="participant_count"
                    class="form-control guide-quick-count-input"
                    value="{{ old('participant_count', '') }}"
                    inputmode="numeric"
                    required
                    autofocus
                >
                <div class="guide-quick-count-buttons mt-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-quick-adjust="-5">−5</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-quick-adjust="-1">−1</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-quick-adjust="1">+1</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-quick-adjust="5">+5</button>
                </div>
            </div>

            <details class="guide-quick-details mb-3">
                <summary class="guide-quick-details-summary">Detaljera m/k/u/b (valfritt)</summary>
                <div class="row g-3 mt-1">
                    @include('partials.bookings.participant-fields', ['booking' => new \App\Models\Booking(), 'compact' => true])
                </div>
            </details>

            <div class="col-12">
                <label class="form-label">Språk</label>
                @php
                    $selectedLanguages = collect(old('language_ids', $defaultLanguageIds ?? []))
                        ->map(fn ($id) => (string) $id)
                        ->all();
                    $selectedLanguageNames = collect($languages ?? [])
                        ->filter(fn ($language) => in_array((string) $language->id, $selectedLanguages, true))
                        ->pluck('name');
                @endphp

                <div class="language-chip-selected-summary mb-2" data-language-summary>
                    Valda språk:
                    <strong data-language-summary-text>
                        {{ $selectedLanguageNames->isNotEmpty() ? $selectedLanguageNames->implode(', ') : 'Inga' }}
                    </strong>
                </div>

                <div class="language-chip-grid">
                    @foreach(($languages ?? collect()) as $language)
                        <label class="language-chip-option">
                            <input
                                type="checkbox"
                                name="language_ids[]"
                                value="{{ $language->id }}"
                                data-language-name="{{ $language->name }}"
                                @checked(in_array((string) $language->id, $selectedLanguages, true))
                            >
                            <span class="language-chip-pill">
                                <span class="language-chip-main">
                                    <span class="language-chip-check" aria-hidden="true">
                                        <i class="bi bi-check-circle-fill"></i>
                                    </span>
                                    <span class="language-chip-name">{{ $language->name }}</span>
                                </span>
                                @if(!empty($language->code))
                                    <span class="language-chip-code">{{ strtoupper($language->code) }}</span>
                                @endif
                                <span class="language-chip-selected-label">Vald</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                <div class="form-text">Tryck för att välja eller avmarkera. Svenska är förvalt.</div>
            </div>
        </div>

        <div class="page-card guide-side-panel">
            <div class="info-item mb-3">
                <div class="small-muted mb-1">Guide</div>
                <div class="fw-semibold">{{ auth()->user()->name }}</div>
            </div>

            <div class="guide-primary-actions">
                <button type="submit" class="btn btn-primary btn-lg w-100" @disabled($quickTourBlocked)>
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

.guide-quick-count-input {
    font-size: 2rem;
    font-weight: 800;
    text-align: center;
    min-height: 4.5rem;
}

.guide-quick-count-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
}

.guide-quick-details-summary {
    cursor: pointer;
    font-weight: 700;
    color: #475569;
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

.language-chip-selected-summary {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 12px;
    padding: 0.65rem 0.85rem;
    color: #1e3a8a;
    font-size: 0.92rem;
}

.language-chip-option input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.language-chip-pill {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    min-height: 54px;
    padding: 0.9rem 1rem;
    border-radius: 16px;
    border: 2px solid #dbe3ee;
    background: #fff;
    transition: all 0.18s ease;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.language-chip-main {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    min-width: 0;
}

.language-chip-check {
    display: none;
    color: #16a34a;
    font-size: 1.1rem;
    line-height: 1;
}

.language-chip-selected-label {
    display: none;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #1d4ed8;
    background: rgba(37, 99, 235, 0.12);
    border-radius: 999px;
    padding: 0.2rem 0.45rem;
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
    background: #f8fbff;
}

.language-chip-option input:checked + .language-chip-pill {
    border-color: #2563eb;
    background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.language-chip-option input:checked + .language-chip-pill .language-chip-check,
.language-chip-option input:checked + .language-chip-pill .language-chip-selected-label {
    display: inline-flex;
}

.language-chip-option input:checked + .language-chip-pill .language-chip-code {
    background: rgba(37, 99, 235, 0.14);
    color: #1d4ed8;
}

.language-chip-option input:focus-visible + .language-chip-pill {
    outline: 2px solid #2563eb;
    outline-offset: 2px;
}

@media (max-width: 1100px) {
    .guide-quicktour-layout {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('participant_count');
    if (input) {
        document.querySelectorAll('[data-quick-adjust]').forEach(function (button) {
            button.addEventListener('click', function () {
                const delta = parseInt(button.getAttribute('data-quick-adjust'), 10) || 0;
                const current = parseInt(input.value, 10) || 0;
                input.value = Math.max(1, current + delta);
            });
        });
    }

    const summaryText = document.querySelector('[data-language-summary-text]');
    const languageInputs = Array.from(document.querySelectorAll('input[name="language_ids[]"]'));

    function updateLanguageSummary() {
        if (!summaryText) {
            return;
        }

        const names = languageInputs
            .filter(function (checkbox) { return checkbox.checked; })
            .map(function (checkbox) { return checkbox.getAttribute('data-language-name') || ''; })
            .filter(Boolean);

        summaryText.textContent = names.length > 0 ? names.join(', ') : 'Inga';
    }

    languageInputs.forEach(function (checkbox) {
        checkbox.addEventListener('change', updateLanguageSummary);
    });

    updateLanguageSummary();
});
</script>
@endsection
