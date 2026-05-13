<div class="page-card">
    <div class="special-tour-grid">
        <div class="special-tour-main">
            <div class="section-title">Turinformation</div>

            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <label class="form-label">Namn på tur</label>
                    <input
                        type="text"
                        name="title"
                        class="form-control"
                        value="{{ old('title', $tour->title ?? '') }}"
                    >
                    <div class="form-text">Lämna tomt för att låta systemet skapa namn automatiskt.</div>
                </div>

                <div class="col-md-4">
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
                </div>

                <div class="col-md-4">
                    <label class="form-label">Max deltagare</label>
                    <input
                        type="number"
                        min="1"
                        name="max_participants"
                        class="form-control"
                        value="{{ old('max_participants', $tour->max_participants ?? 25) }}"
                        required
                    >
                </div>

                @php
                    $prefix = \App\Support\ActiveRole::routePrefix();
                @endphp

                <div class="col-md-4">
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

                            <option value="{{ $guide->id }}"
                                    data-shift-start="{{ $guideShift?->start_time ? substr($guideShift->start_time, 0, 5) : '' }}"
                                    data-has-shift="{{ $guideShift ? '1' : '0' }}"
                                    data-has-conflict="0"
                                    data-conflict-text=""
                                    @selected(old('guide_id', $tour->guide_id ?? '') == $guide->id)>
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

                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['planned' => 'Planerad', 'started' => 'Startad', 'completed' => 'Avslutad', 'cancelled' => 'Inställd'] as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', $tour->status ?? 'planned') === $key)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Intern beskrivning av turen</label>
                    <textarea name="description" class="form-control" rows="5">{{ old('description', $tour->description ?? '') }}</textarea>
                </div>
            </div>

            <div class="section-title">Publik bokningssida</div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Publik slug</label>
                    <input
                        type="text"
                        name="slug"
                        class="form-control"
                        value="{{ old('slug', $bookingPage->slug ?? '') }}"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Sidrubrik</label>
                    <input
                        type="text"
                        name="page_title"
                        class="form-control"
                        value="{{ old('page_title', $bookingPage->page_title ?? '') }}"
                        required
                    >
                </div>

                <div class="col-12">
                    <label class="form-label">Sidtext</label>
                    <textarea name="page_text" class="form-control special-textarea-lg" rows="10">{{ old('page_text', $bookingPage->page_text ?? '') }}</textarea>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Vuxenpris</label>
                    <input type="number" step="0.01" min="0" name="adult_price" class="form-control" value="{{ old('adult_price', $bookingPage->adult_price ?? 0) }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Ungdomspris</label>
                    <input type="number" step="0.01" min="0" name="youth_price" class="form-control" value="{{ old('youth_price', $bookingPage->youth_price ?? 0) }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Barnpris</label>
                    <input type="number" step="0.01" min="0" name="child_price" class="form-control" value="{{ old('child_price', $bookingPage->child_price ?? 0) }}" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Tacktext efter bokning</label>
                    <textarea name="thank_you_text" class="form-control special-textarea-md" rows="6">{{ old('thank_you_text', $bookingPage->thank_you_text ?? '') }}</textarea>
                </div>

                <div class="col-12">
                    <label class="form-label">Text när turen är full</label>
                    <textarea name="full_tour_text" class="form-control special-textarea-md" rows="6">{{ old('full_tour_text', $bookingPage->full_tour_text ?? '') }}</textarea>
                </div>

                <div class="col-12">
                    <label class="form-label">Bokningsvillkor</label>
                    <textarea name="booking_terms" class="form-control special-textarea-lg" rows="10">{{ old('booking_terms', $bookingPage->booking_terms ?? '') }}</textarea>
                </div>

                <div class="col-12">
                    <label class="form-label">Bekräftelsemail – ämne</label>
                    <input
                        type="text"
                        name="confirmation_subject"
                        class="form-control"
                        value="{{ old('confirmation_subject', $bookingPage->confirmation_subject ?? '') }}"
                    >
                </div>

                <div class="col-12">
                    <label class="form-label">Bekräftelsemail – text</label>
                    <textarea name="confirmation_body" class="form-control special-textarea-xl mail-textarea" rows="14">{{ old('confirmation_body', $bookingPage->confirmation_body ?? '') }}</textarea>
                    <div class="form-text">
                        Du kan använda variabler som
                        @{{contact_name}},
                        @{{tour_title}},
                        @{{tour_date}},
                        @{{start_time}},
                        @{{total_count}}.
                    </div>
                </div>
            </div>
        </div>

        <div class="special-tour-side">
            <div class="form-side-box">
                <div class="section-title">Publicering</div>

                <div class="form-check mb-4">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="is_public"
                        value="1"
                        id="is_public"
                        @checked(old('is_public', $bookingPage->is_public ?? true))
                    >
                    <label class="form-check-label" for="is_public">
                        Publik bokningssida aktiv
                    </label>
                </div>

                <div class="small-muted mb-3">
                    Skapar eller uppdaterar både turen och den publika bokningssidan i samma steg.
                </div>

                <button class="btn btn-primary w-100">
                    <i class="bi bi-save me-2"></i>Spara specialtur
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.special-tour-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 300px;
    gap: 1rem;
    align-items: start;
}

.special-textarea-md {
    min-height: 160px !important;
}

.special-textarea-lg {
    min-height: 240px !important;
}

.special-textarea-xl {
    min-height: 340px !important;
}

.mail-textarea {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    line-height: 1.55;
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
    .special-tour-grid {
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