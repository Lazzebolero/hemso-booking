@csrf

@php
    use App\Models\TourType;

    $tourTypesList = isset($tourTypes) ? collect($tourTypes) : collect();

    if ($tourTypesList->isEmpty()) {
        $tourTypesList = TourType::activeOrdered();
    }

    $defaultIncludesMeal = (string) old('default_includes_meal', $tour->default_includes_meal ? '1' : '0') === '1';
@endphp

<div class="page-card">
    <div class="section-title">Turinformation</div>

    <div class="tour-form-grid">
        <div class="tour-form-main">
            <div class="row g-3">
                @if($tour->exists ?? false)
                    <div class="col-12">
                        <div class="guide-edit-highlight">
                            @include('admin.tours._guide-field', ['guideFieldClass' => ''])
                            @include('admin.tours._co-guides-field')

                            <div class="form-check mt-3">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="ripple_subsequent_guides"
                                    value="1"
                                    id="ripple_subsequent_guides"
                                    @checked(old('ripple_subsequent_guides', '1') === '1')
                                >
                                <label class="form-check-label" for="ripple_subsequent_guides">
                                    Uppdatera efterföljande turer samma dag enligt dagens guideordning
                                </label>
                                <div class="form-text">
                                    Pågående och avslutade turer ändras inte. Gäller när du byter guide.
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="col-12">
                    <label class="form-label">Namn på tur</label>
                    <input
                        type="text"
                        name="title"
                        class="form-control"
                        value="{{ old('title', $tour->title ?? '') }}"
                    >
                    <div class="form-text">Lämna tomt för att låta systemet skapa ett namn automatiskt.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Turtyp</label>
                    <select name="tour_type_id" class="form-select" required>
                        <option value="">Välj turtyp</option>
                        @forelse($tourTypesList as $tourType)
                            <option value="{{ $tourType->id }}"
                                @selected(old('tour_type_id', $tour->tour_type_id ?? $defaultTourTypeId ?? '') == $tourType->id)>
                                {{ $tourType->name }}
                            </option>
                        @empty
                            <option value="" disabled>Inga turtyper hittades — skapa under Inställningar</option>
                        @endforelse
                    </select>
                    @if($tourTypesList->isEmpty())
                        <div class="form-text text-danger">
                            Lägg till minst en turtyp under Inställningar → Turtyper.
                        </div>
                    @endif
                </div>

                <div class="col-md-6">
                    <label class="form-label">Mat (standard för bokningar)</label>
                    <select name="default_includes_meal" class="form-select">
                        <option value="0" @selected(! $defaultIncludesMeal)>Ej mat</option>
                        <option value="1" @selected($defaultIncludesMeal)>Med mat</option>
                    </select>
                    <div class="form-text">Gäller som förval när nya bokningar görs på turen. Kan ändras per bokning.</div>
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="exclude_from_booking_sequence"
                            value="1"
                            id="exclude_from_booking_sequence"
                            @checked(old('exclude_from_booking_sequence', $tour->exclude_from_booking_sequence ?? false))
                        >
                        <label class="form-check-label" for="exclude_from_booking_sequence">
                            Exkludera från bokningssekvens
                        </label>
                        <div class="form-text">
                            Turen visas inte i bokningssekvensen även om turtypen är vald där. Endast turtyper markerade under Inställningar → Turtyper ingår i sekvensen.
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="exclude_from_schedule_statistics"
                            value="1"
                            id="exclude_from_schedule_statistics"
                            @checked(old('exclude_from_schedule_statistics', $tour->exclude_from_schedule_statistics ?? false))
                        >
                        <label class="form-check-label" for="exclude_from_schedule_statistics">
                            Exkludera från datum- och tidsstatistik
                        </label>
                        <div class="form-text">
                            Besökare räknas kvar i totaler och fördelning, men turen påverkar inte veckodagar, populära tider eller årets topplista över dagar.
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['planned' => 'Planerad', 'started' => 'Startad', 'completed' => 'Avslutad', 'cancelled' => 'Inställd'] as $statusKey => $statusLabel)
                            <option value="{{ $statusKey }}" @selected(old('status', $tour->status ?? 'planned') === $statusKey)>
                                {{ $statusLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Datum</label>
                    <input
                        type="date"
                        name="tour_date"
                        id="tour_date"
                        class="form-control"
                        value="{{ old('tour_date', !empty($tour->tour_date) ? \Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d') : '') }}"
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Starttid</label>
                    <input
                        type="time"
                        name="start_time"
                        id="start_time"
                        class="form-control"
                        value="{{ old('start_time', !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '') }}"
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Sluttid</label>
                    <input
                        type="time"
                        name="end_time"
                        id="end_time"
                        class="form-control"
                        value="{{ old('end_time', !empty($tour->end_time) ? substr($tour->end_time, 0, 5) : '') }}"
                    >
                    <div class="form-text">Lämna tomt för att använda turtypens standardlängd.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Max antal deltagare</label>
                    <input
                        type="number"
                        min="1"
                        name="max_participants"
                        class="form-control"
                        value="{{ old('max_participants', $tour->max_participants ?? 20) }}"
                        required
                    >
                </div>

                @unless($tour->exists ?? false)
                    @include('admin.tours._guide-field')
                    @include('admin.tours._co-guides-field')
                @endunless

                <div class="col-12">
                    <label class="form-label">Beskrivning</label>
                    <textarea name="description" class="form-control" rows="6">{{ old('description', $tour->description ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="tour-form-side">
            <div class="form-side-box">
                <div class="info-label">Spara</div>
                <div class="small-muted mb-3">
                    Kontrollera datum, tider, guide och kapacitet innan du sparar.
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-save me-2"></i>Spara tur
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.tour-form-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 280px;
    gap: 1rem;
    align-items: start;
}

.form-side-box {
    background: #f8fafc;
    border: 1px solid var(--brand-line-soft);
    border-radius: 12px;
    padding: 0.9rem;
}

.guide-edit-highlight {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 12px;
    padding: 1rem 1.1rem;
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
    border-color: #16a34a;
}

.guide-schedule-warn {
    background: #f59e0b;
    color: #ffffff;
    border-color: #d97706;
}

.guide-schedule-danger {
    background: #ef4444;
    color: #ffffff;
    border-color: #dc2626;
}

@media (max-width: 1100px) {
    .tour-form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
    function updateGuideScheduleStatus() {
        const guideSelect = document.getElementById('guide_id');
        const startTimeInput = document.getElementById('start_time');
        const statusBox = document.getElementById('guide_schedule_status');
        const badge = document.getElementById('guide_schedule_badge');
        const text = document.getElementById('guide_schedule_text');

        if (!guideSelect || !startTimeInput || !statusBox || !badge || !text) {
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
        const tourStart = startTimeInput.value || '';

        statusBox.classList.remove('d-none');
        badge.className = 'guide-schedule-badge';

        if (hasConflict) {
            badge.classList.add('guide-schedule-danger');
            badge.textContent = 'Turkrock';
            text.textContent = conflictText
                ? 'Guiden har redan en annan tur: ' + conflictText + '.'
                : 'Guiden har redan en annan tur samma tid.';
            return;
        }

        const hasLanguageMismatch = selectedOption.dataset.hasLanguageMismatch === '1';
        const missingLanguageCodes = selectedOption.dataset.missingLanguageCodes || '';

        if (hasLanguageMismatch) {
            badge.classList.add('guide-schedule-warn');
            badge.textContent = 'Språkmismatch';
            text.textContent = missingLanguageCodes
                ? 'Guiden saknar bokade språk: ' + missingLanguageCodes + '.'
                : 'Guiden matchar inte turprogrammets språk.';
            return;
        }

        if (!hasShift) {
            badge.classList.add('guide-schedule-danger');
            badge.textContent = 'Inget pass';
            text.textContent = 'Guiden saknar arbetspass detta datum.';
            return;
        }

        if (shiftStart && tourStart && shiftStart > tourStart) {
            badge.classList.add('guide-schedule-warn');
            badge.textContent = 'Börjar senare';
            text.textContent = 'Arbetspass börjar ' + shiftStart + ', men turen startar ' + tourStart + '.';
            return;
        }

        badge.classList.add('guide-schedule-ok');
        badge.textContent = 'Pass OK';
        text.textContent = shiftStart
            ? 'Arbetspass börjar ' + shiftStart + '.'
            : 'Guiden har arbetspass detta datum.';
    }

    async function refreshGuideAvailability() {
        const dateInput = document.getElementById('tour_date');
        const startTimeInput = document.getElementById('start_time');
        const endTimeInput = document.getElementById('end_time');
        const guideSelect = document.getElementById('guide_id');

        if (!dateInput || !guideSelect || !dateInput.value) {
            return;
        }

        const url = guideSelect.dataset.availabilityUrl;

        if (!url) {
            return;
        }

        const currentValue = guideSelect.value;

        const params = new URLSearchParams({
            date: dateInput.value,
        });

        if (startTimeInput && startTimeInput.value) {
            params.append('start_time', startTimeInput.value);
        }

        if (endTimeInput && endTimeInput.value) {
            params.append('end_time', endTimeInput.value);
        }

        if (guideSelect.dataset.ignoreTourId) {
            params.append('ignore_tour_id', guideSelect.dataset.ignoreTourId);
        }

        if (guideSelect.dataset.tourId) {
            params.append('tour_id', guideSelect.dataset.tourId);
        }

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
                option.dataset.hasLanguageMismatch = guide.has_language_mismatch ? '1' : '0';
                option.dataset.missingLanguageCodes = (guide.missing_language_codes || []).join(', ');

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

    document.addEventListener('DOMContentLoaded', function () {
        const guideSelect = document.getElementById('guide_id');
        const startTimeInput = document.getElementById('start_time');
        const endTimeInput = document.getElementById('end_time');
        const dateInput = document.getElementById('tour_date');

        if (guideSelect) {
            guideSelect.addEventListener('change', updateGuideScheduleStatus);
        }

        if (startTimeInput) {
            startTimeInput.addEventListener('change', refreshGuideAvailability);
            startTimeInput.addEventListener('input', refreshGuideAvailability);
        }

        if (endTimeInput) {
            endTimeInput.addEventListener('change', refreshGuideAvailability);
            endTimeInput.addEventListener('input', refreshGuideAvailability);
        }

        if (dateInput) {
            dateInput.addEventListener('change', refreshGuideAvailability);
        }

        refreshGuideAvailability();
    });
</script>