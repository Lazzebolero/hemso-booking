@csrf

<div class="page-card">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="section-title">Turinformation</div>

            <div class="mb-3">
                <label class="form-label">Namn på tur</label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $tour->title ?? '') }}">
                <div class="small muted mt-2">Lämna tomt för att låta systemet skapa ett namn automatiskt.</div>
            </div>

            <div class="mb-3">
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

            <div class="mb-3">
                <label class="form-label">Beskrivning</label>
                <textarea name="description" class="form-control" rows="5">{{ old('description', $tour->description ?? '') }}</textarea>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Datum</label>
                    <input
                        type="date"
                        name="tour_date"
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
                        class="form-control"
                        value="{{ old('end_time', !empty($tour->end_time) ? substr($tour->end_time, 0, 5) : '') }}"
                    >
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="page-card h-100">
                <div class="section-title">Inställningar</div>

                <div class="mb-3">
                    <label class="form-label">Max antal deltagare</label>
                    <input type="number" min="1" name="max_participants" class="form-control" value="{{ old('max_participants', $tour->max_participants ?? 20) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Guide</label>
                    <select name="guide_id" class="form-select">
                        <option value="">Ej tilldelad</option>
                        @foreach($guides as $guide)
                            <option value="{{ $guide->id }}" @selected(old('guide_id', $tour->guide_id ?? '') == $guide->id)>
                                {{ $guide->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['planned' => 'Planerad', 'started' => 'Startad', 'completed' => 'Avslutad', 'cancelled' => 'Inställd'] as $statusKey => $statusLabel)
                            <option value="{{ $statusKey }}" @selected(old('status', $tour->status ?? 'planned') === $statusKey)>
                                {{ $statusLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button class="btn btn-primary w-100">
                    <i class="bi bi-save me-2"></i>Spara tur
                </button>
            </div>
        </div>
    </div>
</div>