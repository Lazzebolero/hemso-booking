@php
    $coGuideCollection = $tour->relationLoaded('coGuides') ? $tour->coGuides : collect();
    $availableGuides = $guides ?? collect();
    $availableTrainees = $traineeCandidates ?? $availableGuides;

    $selectedAssistantIds = collect(old(
        'assistant_guide_ids',
        $coGuideCollection->where('pivot.role', 'assistant')->pluck('id')->all()
    ))
        ->map(fn ($id) => (string) $id)
        ->all();

    $selectedTraineeIds = collect(old(
        'trainee_guide_ids',
        $coGuideCollection->where('pivot.role', 'trainee')->pluck('id')->all()
    ))
        ->map(fn ($id) => (string) $id)
        ->all();

    $coGuideNotes = old(
        'co_guide_notes',
        $coGuideCollection
            ->mapWithKeys(fn ($guide) => [$guide->id => $guide->pivot->notes ?? ''])
            ->all()
    );

    $leadGuideId = (string) old('guide_id', $tour->guide_id ?? '');
@endphp

<div class="col-12" id="co-guides-field">
    <div class="section-title mb-2">Medguider</div>
    <div class="form-text mb-3">
        Assistenter måste vara guider. Under Trainees listas elev, värd och restaurang som inte också har guide-roll.
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <label class="form-label">Assistenter</label>
            <div class="co-guide-checkbox-list">
                @forelse($availableGuides as $guide)
                    @if((string) $guide->id === $leadGuideId)
                        @continue
                    @endif
                    @php
                        $isSelectedAssistant = in_array((string) $guide->id, $selectedAssistantIds, true);
                    @endphp
                    <div class="co-guide-row" data-guide-id="{{ $guide->id }}">
                        <label class="form-check mb-1">
                            <input
                                type="checkbox"
                                class="form-check-input co-guide-assistant-checkbox"
                                name="assistant_guide_ids[]"
                                value="{{ $guide->id }}"
                                data-guide-id="{{ $guide->id }}"
                                @checked($isSelectedAssistant)
                            >
                            <span class="form-check-label">{{ $guide->name }}</span>
                        </label>
                        <input
                            type="text"
                            class="form-control form-control-sm co-guide-note-input"
                            name="co_guide_notes[{{ $guide->id }}]"
                            value="{{ $coGuideNotes[$guide->id] ?? '' }}"
                            placeholder="t.ex. Engelska grupp"
                            maxlength="255"
                            data-guide-id="{{ $guide->id }}"
                            @disabled(! $isSelectedAssistant)
                        >
                    </div>
                @empty
                    <div class="small-muted">Inga guider hittades.</div>
                @endforelse
            </div>
        </div>

        <div class="col-md-6">
            <label class="form-label">Trainees</label>
            <div class="co-guide-checkbox-list">
                @forelse($availableTrainees as $guide)
                    @if((string) $guide->id === $leadGuideId)
                        @continue
                    @endif
                    @php
                        $isSelectedTrainee = in_array((string) $guide->id, $selectedTraineeIds, true);
                    @endphp
                    <div class="co-guide-row" data-guide-id="{{ $guide->id }}">
                        <label class="form-check mb-1">
                            <input
                                type="checkbox"
                                class="form-check-input co-guide-trainee-checkbox"
                                name="trainee_guide_ids[]"
                                value="{{ $guide->id }}"
                                data-guide-id="{{ $guide->id }}"
                                @checked($isSelectedTrainee)
                            >
                            <span class="form-check-label">{{ $guide->name }}</span>
                        </label>
                        <input
                            type="text"
                            class="form-control form-control-sm co-guide-note-input"
                            name="co_guide_notes[{{ $guide->id }}]"
                            value="{{ $coGuideNotes[$guide->id] ?? '' }}"
                            placeholder="t.ex. Utbildning"
                            maxlength="255"
                            data-guide-id="{{ $guide->id }}"
                            @disabled(! $isSelectedTrainee)
                        >
                    </div>
                @empty
                    <div class="small-muted">Ingen personal hittades för Trainees.</div>
                @endforelse
            </div>
        </div>
    </div>

    @error('assistant_guide_ids')
        <div class="text-danger small mt-2">{{ $message }}</div>
    @enderror
    @error('trainee_guide_ids')
        <div class="text-danger small mt-2">{{ $message }}</div>
    @enderror
    @error('co_guide_notes.*')
        <div class="text-danger small mt-2">{{ $message }}</div>
    @enderror
</div>

<style>
    .co-guide-checkbox-list {
        display: grid;
        gap: 0.75rem;
        max-height: 360px;
        overflow-y: auto;
        padding: 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #f8fafc;
    }

    .co-guide-row.is-hidden {
        display: none;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const guideSelect = document.getElementById('guide_id');
        const coGuidesField = document.getElementById('co-guides-field');

        if (!guideSelect || !coGuidesField) {
            return;
        }

        function noteInputForGuide(guideId, role) {
            const row = coGuidesField.querySelector('.co-guide-' + role + '-checkbox[data-guide-id="' + guideId + '"]')?.closest('.co-guide-row');

            return row ? row.querySelector('.co-guide-note-input') : null;
        }

        function syncNoteState(guideId) {
            const assistant = coGuidesField.querySelector('.co-guide-assistant-checkbox[data-guide-id="' + guideId + '"]');
            const trainee = coGuidesField.querySelector('.co-guide-trainee-checkbox[data-guide-id="' + guideId + '"]');
            const assistantNote = noteInputForGuide(guideId, 'assistant');
            const traineeNote = noteInputForGuide(guideId, 'trainee');

            if (assistantNote) {
                assistantNote.disabled = !(assistant && assistant.checked);
            }

            if (traineeNote) {
                traineeNote.disabled = !(trainee && trainee.checked);
            }
        }

        function syncCoGuideVisibility() {
            const leadId = guideSelect.value || '';

            coGuidesField.querySelectorAll('.co-guide-row[data-guide-id]').forEach(function (row) {
                const guideId = row.getAttribute('data-guide-id');

                if (leadId !== '' && guideId === leadId) {
                    row.classList.add('is-hidden');
                    row.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
                        checkbox.checked = false;
                    });
                } else {
                    row.classList.remove('is-hidden');
                }

                syncNoteState(guideId);
            });
        }

        function preventDualSelection(changed) {
            const guideId = changed.getAttribute('data-guide-id');
            const assistant = coGuidesField.querySelector('.co-guide-assistant-checkbox[data-guide-id="' + guideId + '"]');
            const trainee = coGuidesField.querySelector('.co-guide-trainee-checkbox[data-guide-id="' + guideId + '"]');

            if (!assistant || !trainee) {
                syncNoteState(guideId);

                return;
            }

            if (changed.checked) {
                if (changed.classList.contains('co-guide-assistant-checkbox')) {
                    trainee.checked = false;
                } else {
                    assistant.checked = false;
                }
            }

            syncNoteState(guideId);
        }

        guideSelect.addEventListener('change', syncCoGuideVisibility);

        coGuidesField.querySelectorAll('.co-guide-assistant-checkbox, .co-guide-trainee-checkbox').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                preventDualSelection(checkbox);
            });
        });

        syncCoGuideVisibility();
    });
</script>
