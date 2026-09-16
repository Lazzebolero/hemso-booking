@php
    $cardClass = $cardClass ?? 'page-card mb-3';
    $showTourPicker = $showTourPicker ?? false;
    $allowAudioFileUpload = $allowAudioFileUpload ?? false;
    $tourOptions = $tourOptions ?? collect();
@endphp

<form method="POST"
      action="{{ $formAction }}"
      enctype="multipart/form-data"
      id="facility-memory-form">
    @csrf

    @if($tour && ! $showTourPicker)
        <input type="hidden" name="tour_id" value="{{ $tour->id }}">
    @endif

    <div class="{{ $cardClass }}">
        <div class="section-title mb-3">Typ av minne</div>

        <div class="btn-group w-100" role="group" aria-label="Typ av minne">
            <input type="radio" class="btn-check" name="type" id="memory-type-text" value="text" @checked(old('type', 'text') === 'text')>
            <label class="btn btn-outline-primary" for="memory-type-text">
                <i class="bi bi-pencil-square me-1"></i>Skriv
            </label>

            <input type="radio" class="btn-check" name="type" id="memory-type-audio" value="audio" @checked(old('type') === 'audio')>
            <label class="btn btn-outline-primary" for="memory-type-audio">
                <i class="bi bi-mic me-1"></i>Spela in
            </label>
        </div>
    </div>

    <div class="{{ $cardClass }}" id="memory-text-panel">
        <label for="body" class="form-label fw-semibold">Berättelsen</label>
        <textarea
            name="body"
            id="body"
            class="form-control"
            rows="8"
            maxlength="20000"
            placeholder="Vad berättade personen? Skriv med egna ord."
        >{{ old('body') }}</textarea>
        @error('body')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
    </div>

    <div class="{{ $cardClass }} d-none" id="memory-audio-panel">
        <div class="section-title mb-2">Ljudinspelning</div>
        <p class="text-muted small mb-3">
            Fråga besökaren om lov innan inspelning. Max 10 minuter.
        </p>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <button type="button" id="memory-audio-start" class="btn btn-outline-danger">
                <i class="bi bi-record-circle me-1"></i>Starta inspelning
            </button>
            <button type="button" id="memory-audio-stop" class="btn btn-outline-secondary" disabled>
                <i class="bi bi-stop-circle me-1"></i>Stoppa
            </button>
            <button type="button" id="memory-audio-retake" class="btn btn-outline-secondary d-none">
                Spela in igen
            </button>
        </div>

        <div id="memory-audio-status" class="form-text mb-3">
            Tryck på "Starta inspelning" när du har fått samtycke.
        </div>

        <div id="memory-audio-timer" class="fw-semibold mb-3 d-none">00:00 / 10:00</div>

        <audio id="memory-audio-playback" class="w-100 mb-3 d-none" controls></audio>

        <input
            type="file"
            name="audio"
            id="audio"
            class="{{ $allowAudioFileUpload ? 'form-control mb-3' : 'd-none' }}"
            accept="audio/*"
        >

        @if($allowAudioFileUpload)
            <div class="form-text mb-3">Eller spela in med knapparna ovan. Max 15 MB.</div>
        @endif

        <input type="hidden" name="audio_duration_seconds" id="audio_duration_seconds" value="{{ old('audio_duration_seconds') }}">

        @error('audio')
            <div class="text-danger mb-3">{{ $message }}</div>
        @enderror

        <label for="context_note" class="form-label fw-semibold">Kort kontext (valfritt)</label>
        <textarea
            name="context_note"
            id="context_note"
            class="form-control"
            rows="3"
            maxlength="2000"
            placeholder="Ex. Besökare vid mansköket, berättade om julbordet under värnplikten."
        >{{ old('context_note') }}</textarea>
        @error('context_note')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
    </div>

    <div class="{{ $cardClass }}">
        <div class="row g-3">
            @if($showTourPicker)
                <div class="col-12">
                    <label for="tour_id" class="form-label">Koppla till tur (valfritt)</label>
                    <select name="tour_id" id="tour_id" class="form-select">
                        <option value="">Ingen tur</option>
                        @foreach($tourOptions as $optionTour)
                            <option
                                value="{{ $optionTour->id }}"
                                @selected((string) old('tour_id', $tour?->id) === (string) $optionTour->id)
                            >
                                {{ $optionTour->tour_date?->format('Y-m-d') }}
                                {{ ! empty($optionTour->start_time) ? substr($optionTour->start_time, 0, 5) : '' }}
                                — {{ $optionTour->tourType?->name ?? 'Tur' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-md-6">
                <label for="location_id" class="form-label">Plats</label>
                <select name="location_id" id="location_id" class="form-select">
                    <option value="">Välj plats</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) old('location_id') === (string) $location->id)>
                            {{ $location->name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Samma platslista som vid felrapporter.</div>
            </div>

            <div class="col-md-6">
                <label for="location_text" class="form-label">Fri platsbeskrivning</label>
                <input
                    type="text"
                    name="location_text"
                    id="location_text"
                    class="form-control"
                    maxlength="255"
                    value="{{ old('location_text') }}"
                    placeholder="Om platsen inte finns i listan"
                >
            </div>

            <div class="col-md-6">
                <label for="era_text" class="form-label">Ungefärlig tid</label>
                <input
                    type="text"
                    name="era_text"
                    id="era_text"
                    class="form-control"
                    maxlength="120"
                    value="{{ old('era_text') }}"
                    placeholder="Ex. 1970-talet, ca 1973"
                >
            </div>

            <div class="col-md-6">
                <label for="visitor_name" class="form-label">Besökarens namn (valfritt)</label>
                <input
                    type="text"
                    name="visitor_name"
                    id="visitor_name"
                    class="form-control"
                    maxlength="120"
                    value="{{ old('visitor_name') }}"
                    placeholder="Lämna tomt om anonymt"
                >
            </div>
        </div>
    </div>

    <div class="{{ $cardClass }}">
        <div class="form-check">
            <input
                class="form-check-input"
                type="checkbox"
                value="1"
                name="consent_given"
                id="consent_given"
                @checked(old('consent_given'))
                required
            >
            <label class="form-check-label" for="consent_given" id="consent_given_label">
                Personen godkände att vi sparar minnet internt för dokumentation.
            </label>
        </div>
        @error('consent_given')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary btn-lg" id="memory-submit-button">
            <i class="bi bi-archive me-2"></i>Spara minne
        </button>
        <a href="{{ $cancelUrl }}" class="btn btn-outline-secondary btn-lg">Avbryt</a>
    </div>
</form>

@include('partials.facility-memories.form-script', [
    'allowAudioFileUpload' => $allowAudioFileUpload,
])
