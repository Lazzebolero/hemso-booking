@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Starta snabbtur</h2>
        <div class="page-subtitle">Skapa en extra tur direkt och starta den omedelbart.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<form method="POST" action="{{ route('quick-tours.store') }}" data-offline-queue>
    @csrf

    <div class="form-layout">
        <div class="page-card">
            <div class="section-title">Deltagare och språk</div>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Män</label>
                    <input
                        type="number"
                        min="0"
                        name="men_count"
                        class="form-control"
                        value="{{ old('men_count', 0) }}"
                        required
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">Kvinnor</label>
                    <input
                        type="number"
                        min="0"
                        name="women_count"
                        class="form-control"
                        value="{{ old('women_count', 0) }}"
                        required
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">Ungdomar</label>
                    <input
                        type="number"
                        min="0"
                        name="youth_count"
                        class="form-control"
                        value="{{ old('youth_count', 0) }}"
                        required
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">Barn</label>
                    <input
                        type="number"
                        min="0"
                        name="child_count"
                        class="form-control"
                        value="{{ old('child_count', 0) }}"
                        required
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
                        @forelse(($languages ?? collect()) as $language)
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
                        @empty
                            <p>Inga språk tillgängliga.</p>
                        @endforelse
                    </div>

                    <div class="form-text">Svenska är förvald, men du kan välja fler språk.</div>
                </div>

                <div class="col-12">
                    <label class="form-label">Anteckning</label>
                    <textarea
                        name="notes"
                        class="form-control quicktour-textarea"
                        rows="6"
                    >{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="form-side-box">
            <div class="section-title">Inställningar</div>

            <div class="mb-3">
                <label class="form-label">Guide</label>
                <select
                    name="guide_id"
                    id="guide_id"
                    class="form-select"
                    data-availability-url="{{ route($prefix . '.guides.availability') }}"
                >
                    <option value="">Ej tilldelad</option>
                    @foreach(($guides ?? collect()) as $guide)
                        @php
                            $guideShift = $guide->workShifts->first();
                            $guideLabel = $guide->name;

                            if ($guideShift && !empty($guideShift->start_time)) {
                                $guideLabel .= ' [' . substr($guideShift->start_time, 0, 5) . ']';
                            } else {
                                $guideLabel .= ' [Inget pass]';
                            }
                        @endphp

                        <option value="{{ $guide->id }}"
                                data-shift-start="{{ $guideShift?->start_time ? substr($guideShift->start_time, 0, 5) : '' }}"
                                data-has-shift="{{ $guideShift ? '1' : '0' }}"
                                data-has-conflict="0"
                                data-conflict-text=""
                                @selected((string) old('guide_id') === (string) $guide->id)>
                            {{ $guideLabel }}
                        </option>
                    @endforeach
                </select>

                <div class="form-text">
                    Alla guider visas. Schemainfo och turkrock uppdateras utifrån aktuell tid.
                </div>

                <div id="guide_schedule_status" class="guide-schedule-status d-none mt-2">
                    <span id="guide_schedule_badge" class="guide-schedule-badge"></span>
                    <span id="guide_schedule_text" class="guide-schedule-text"></span>
                </div>
            </div>

            <div class="info-item mb-3">
                <div class="fw-semibold mb-1">Detta händer när du sparar</div>
                <div class="small-muted">
                    Systemet skapar automatiskt:<br>
                    • en tur med status <strong>startad</strong><br>
                    • en bokning kopplad till turen<br>
                    • turtyp <strong>Snabbtur</strong><br>
                    • språk enligt dina val
                </div>
            </div>

            <button class="btn btn-primary w-100" type="submit">
                <i class="bi bi-play-circle me-2"></i>Starta snabbtur
            </button>
        </div>
    </div>
</form>

<style>
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

.form-side-box {
    background: #f8fafc;
    border: 1px solid var(--brand-line-soft);
    border-radius: 12px;
    padding: 0.95rem;
}

.guide-schedule-status {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
    padding: 0.75rem 0.9rem;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
}

.guide-schedule-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 28px;
    padding: 0.3rem 0.65rem;
    border-radius: 999px;
    font-size: 0.82rem;
    font-weight: 700;
    line-height: 1;
    white-space: nowrap;
    background: #e5e7eb;
    color: #374151;
    border: 1px solid #d1d5db;
}

.guide-schedule-text {
    font-size: 0.92rem;
    color: #334155;
}

.guide-schedule-ok {
    background: #22c55e;
    color: #ffffff;
    border: 1px solid #16a34a;
}

.guide-schedule-warn {
    background: #f59e0b;
    color: #ffffff;
    border: 1px solid #d97706;
}

.guide-schedule-danger {
    background: #ef4444;
    color: #ffffff;
    border: 1px solid #dc2626;
}

@media (max-width: 1100px) {
    .form-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 900px) {
    .language-chip-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 600px) {
    .language-chip-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function todayString() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function currentTimeString() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        return `${hours}:${minutes}`;
    }

    function updateGuideScheduleStatus() {
        const guideSelect = document.getElementById('guide_id');
        const statusBox = document.getElementById('guide_schedule_status');
        const badge = document.getElementById('guide_schedule_badge');
        const text = document.getElementById('guide_schedule_text');

        if (!guideSelect || !statusBox || !badge || !text) {
            return;
        }

        const selectedOption = guideSelect.options[guideSelect.selectedIndex];

        if (!selectedOption || !selectedOption.value) {
            statusBox.classList.add('d-none');
            badge.className = 'guide-schedule-badge';
            badge.textContent = '';
            text.textContent = '';
            return;
        }

        const hasShift = selectedOption.dataset.hasShift === '1';
        const shiftStart = selectedOption.dataset.shiftStart || '';
        const hasConflict = selectedOption.dataset.hasConflict === '1';
        const conflictText = selectedOption.dataset.conflictText || '';

        statusBox.classList.remove('d-none');
        badge.className = 'guide-schedule-badge';

        if (hasConflict) {
            badge.classList.add('guide-schedule-danger');
            badge.textContent = 'Turkrock';
            text.textContent = conflictText
                ? 'Guiden har redan en annan tur: ' + conflictText + '.'
                : 'Guiden har redan en annan tur just nu.';
            return;
        }

        if (!hasShift) {
            badge.classList.add('guide-schedule-danger');
            badge.textContent = 'Inget pass';
            text.textContent = 'Guiden saknar arbetspass idag.';
            return;
        }

        badge.classList.add('guide-schedule-ok');
        badge.textContent = 'Pass OK';
        text.textContent = shiftStart
            ? 'Arbetspass börjar ' + shiftStart + '.'
            : 'Guiden har arbetspass idag.';
    }

    async function refreshGuideAvailability() {
        const guideSelect = document.getElementById('guide_id');

        if (!guideSelect) {
            return;
        }

        const url = guideSelect.dataset.availabilityUrl;
        if (!url) {
            return;
        }

        const currentValue = guideSelect.value;
        const params = new URLSearchParams({
            date: todayString(),
            start_time: currentTimeString(),
        });

        try {
            const response = await fetch(url + '?' + params.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            });

            if (!response.ok) {
                return;
            }

            const guides = await response.json();

            guideSelect.innerHTML = '';

            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = 'Ej tilldelad';
            guideSelect.appendChild(emptyOption);

            guides.forEach(function (guide) {
                const option = document.createElement('option');
                option.value = guide.id;
                option.textContent = guide.label;
                option.dataset.hasShift = guide.has_shift ? '1' : '0';
                option.dataset.shiftStart = guide.shift_start || '';
                option.dataset.hasConflict = guide.has_conflict ? '1' : '0';
                option.dataset.conflictText = guide.conflict_text || '';

                if (String(guide.id) === String(currentValue)) {
                    option.selected = true;
                }

                guideSelect.appendChild(option);
            });

            updateGuideScheduleStatus();
        } catch (error) {
            console.error('Kunde inte hämta guideinfo', error);
        }
    }

    const guideSelect = document.getElementById('guide_id');
    if (guideSelect) {
        guideSelect.addEventListener('change', updateGuideScheduleStatus);
    }

    refreshGuideAvailability();

    setInterval(refreshGuideAvailability, 60000);
});
</script>
@endsection