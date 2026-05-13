@csrf

<div class="page-card">
    <div class="section-title">Turinformation</div>

    <div class="tour-form-grid">
        <div class="tour-form-main">
            <div class="row g-3">
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
                    <select name="tour_type_id" class="form-select">
                        <option value="">Välj turtyp</option>
                        @foreach($tourTypes as $tourType)
                            <option value="{{ $tourType->id }}"
                                @selected(old('tour_type_id', $tour->tour_type_id ?? $defaultTourTypeId ?? '') == $tourType->id)>
                                {{ $tourType->name }}
                            </option>
                        @endforeach
                    </select>
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

                @php
                    $prefix = \App\Support\ActiveRole::routePrefix();
                @endphp

                <div class="col-md-6">
                    <label class="form-label">Guide</label>
                    <select
                        name="guide_id"
                        id="guide_id"
                        class="form-select"
                        data-availability-url="{{ route($prefix . '.guides.availability') }}"
                        data-ignore-tour-id="{{ $tour->id ?? '' }}"
                    >
                        <option value="">Ej tilldelad</option>

                        @foreach($guides as $guide)
                            @php
                                $guideShift = $guide->workShifts->first();
                                $guideLabel = $guide->name;

                                if ($guideShift && !empty($guideShift->start_time)) {
                                    $guideLabel .= ' [' . substr($guideShift->start_time, 0, 5) . ']';
                                } else {
                                    $guideLabel .= ' [Inget pass]';
                                }
                            @endphp

                            <option
                                value="{{ $guide->id }}"
                                data-shift-start="{{ $guideShift?->start_time ? substr($guideShift->start_time, 0, 5) : '' }}"
                                data-has-shift="{{ $guideShift ? '1' : '0' }}"
                                data-has-conflict="0"
                                data-conflict-text=""
                                @selected(old('guide_id', $tour->guide_id ?? '') == $guide->id)
                            >
                                {{ $guideLabel }}
                            </option>
                        @endforeach
                    </select>

                    <div class="form-text">
                        Alla guider visas. Schemainfo och turkrockar uppdateras när datum eller tid ändras.
                    </div>

                    <div id="guide_schedule_status" class="guide-schedule-status d-none mt-2">
                        <span id="guide_schedule_badge" class="guide-schedule-badge"></span>
                        <span id="guide_schedule_text" class="guide-schedule-text"></span>
                    </div>
                </div>

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