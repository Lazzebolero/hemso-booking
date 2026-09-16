@extends('layouts.guide')

@section('content')
@php
    $isCompleted = $check->isCompleted();
    $items = old('items', $check->items ?? []);
@endphp
<div class="page-card mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h2 class="page-title mb-1">Öppningskontroll</h2>
            <div class="page-subtitle">
                Daglig säkerhetsrutin för guider och öppningsansvarig – Hemsö Fästning
            </div>
        </div>
        <a href="{{ route('guide.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success mb-3">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger mb-3">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="guide-focus-card mb-3">
    <div class="small-muted mb-2">Anläggningen får inte öppnas för besökare innan kontrollen är genomförd. En blockerad eller låst utrymningsväg är en säkerhetsavvikelse.</div>
    <div class="guide-muted">
        Datum: <strong>{{ $check->check_date?->format('Y-m-d') }}</strong>
        · Öppningsansvarig: <strong>{{ $check->openedBy?->name ?? auth()->user()->name }}</strong>
        @if($check->started_at)
            · Påbörjad: <strong>{{ $check->started_at->format('H:i') }}</strong>
        @endif
        @if($check->completed_at)
            · Avslutad: <strong>{{ $check->completed_at->format('H:i') }}</strong>
        @endif
    </div>
</div>

<form method="POST" action="{{ route('guide.opening-checks.update') }}">
    @csrf
    @method('PUT')

    <div class="guide-focus-card mb-3">
        <label class="form-label" for="visitor_opens_at">Anläggningen öppnas för besökare kl</label>
        <input
            type="time"
            id="visitor_opens_at"
            name="visitor_opens_at"
            class="form-control"
            value="{{ old('visitor_opens_at', $check->visitorOpensAtInput()) }}"
            @disabled($isCompleted)
            style="max-width: 12rem;"
        >
    </div>

    <div class="guide-focus-card mb-3">
        <div class="section-title mb-3">Daglig öppningskontroll</div>

        @foreach($checkpoints as $key => $label)
            @php
                $outcome = old('items.'.$key, $items[$key] ?? '');
                $existingDeviation = $check->deviations->firstWhere('checkpoint_key', $key);
            @endphp
            <div class="opening-check-item" x-data="{ outcome: @js($outcome) }">
                <div class="opening-check-label">{{ $label }}</div>
                <div class="opening-check-choices">
                    @foreach(['ok' => 'OK', 'deviation' => 'Avvikelse', 'not_applicable' => 'Ej aktuell'] as $value => $choiceLabel)
                        <label class="opening-check-choice">
                            <input
                                type="radio"
                                name="items[{{ $key }}]"
                                value="{{ $value }}"
                                x-model="outcome"
                                @checked($outcome === $value)
                                @disabled($isCompleted)
                            >
                            <span>{{ $choiceLabel }}</span>
                        </label>
                    @endforeach
                </div>

                @if($existingDeviation)
                    <div class="opening-check-existing">
                        Avvikelse redan registrerad
                        @if($existingDeviation->isResolved())
                            · åtgärdad
                        @endif
                    </div>
                @elseif(!$isCompleted)
                    <div class="opening-check-deviation" x-show="outcome === 'deviation'" x-cloak>
                        <label class="form-label">Plats / nödutgång</label>
                        <input type="text" name="deviations[{{ $key }}][location]" class="form-control mb-2" value="{{ old('deviations.'.$key.'.location') }}">

                        <label class="form-label">Beskrivning av avvikelsen</label>
                        <textarea name="deviations[{{ $key }}][description]" class="form-control mb-2" rows="3">{{ old('deviations.'.$key.'.description') }}</textarea>

                        <label class="form-label">Omedelbar åtgärd</label>
                        <textarea name="deviations[{{ $key }}][immediate_action]" class="form-control mb-2" rows="2">{{ old('deviations.'.$key.'.immediate_action') }}</textarea>

                        <label class="form-label">Vem informerades</label>
                        <input type="text" name="deviations[{{ $key }}][informed_person]" class="form-control mb-2" value="{{ old('deviations.'.$key.'.informed_person') }}">

                        <label class="form-label">Beslut innan öppning</label>
                        <textarea name="deviations[{{ $key }}][decision_before_opening]" class="form-control mb-2" rows="2">{{ old('deviations.'.$key.'.decision_before_opening') }}</textarea>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="deviations[{{ $key }}][already_resolved]" value="1" id="resolved-{{ $key }}" @checked(old('deviations.'.$key.'.already_resolved'))>
                            <label class="form-check-label" for="resolved-{{ $key }}">Redan åtgärdad nu</label>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @unless($isCompleted)
        <div class="guide-focus-card mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="confirmed" value="1" id="confirmed" @checked(old('confirmed', $check->confirmed))>
                <label class="form-check-label" for="confirmed">
                    Jag har genomfört kontrollen och ansvarar för uppgifterna.
                </label>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-4">
            <button type="submit" name="intent" value="save" class="btn btn-outline-secondary">Spara utkast</button>
            <button type="submit" name="intent" value="complete" class="btn btn-primary">Slutför och signera</button>
        </div>
    @endunless
</form>

<div class="guide-focus-card mb-3">
    <div class="section-title mb-3">Avvikelserapport</div>

    @forelse($check->deviations as $deviation)
        <div class="opening-deviation-row">
            <div class="fw-semibold">{{ $deviation->checkpointLabel() }}</div>
            <div class="guide-muted mb-1">
                {{ $deviation->occurred_at?->format('H:i') }}
                · {{ $deviation->statusLabel() }}
                · {{ $deviation->reporter?->name }}
            </div>
            <div>{{ $deviation->description }}</div>
            @if($deviation->location)
                <div class="guide-muted">Plats: {{ $deviation->location }}</div>
            @endif
        </div>
    @empty
        <div class="guide-muted">Inga avvikelser registrerade idag.</div>
    @endforelse
</div>

<div class="guide-focus-card mb-4">
    <div class="section-title mb-3">Ny avvikelserapport</div>
    <form method="POST" action="{{ route('guide.opening-checks.deviations.store') }}">
        @csrf
        <label class="form-label">Plats / nödutgång</label>
        <input type="text" name="location" class="form-control mb-2" value="{{ old('location') }}">

        <label class="form-label">Beskrivning av avvikelsen</label>
        <textarea name="description" class="form-control mb-2" rows="3" required>{{ old('description') }}</textarea>

        <label class="form-label">Omedelbar åtgärd</label>
        <textarea name="immediate_action" class="form-control mb-2" rows="2">{{ old('immediate_action') }}</textarea>

        <label class="form-label">Vem informerades</label>
        <input type="text" name="informed_person" class="form-control mb-2" value="{{ old('informed_person') }}">

        <label class="form-label">Beslut innan öppning</label>
        <textarea name="decision_before_opening" class="form-control mb-2" rows="2">{{ old('decision_before_opening') }}</textarea>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="already_resolved" value="1" id="extra-resolved" @checked(old('already_resolved'))>
            <label class="form-check-label" for="extra-resolved">Redan åtgärdad nu</label>
        </div>

        <button type="submit" class="btn btn-primary">Skicka avvikelserapport</button>
    </form>
</div>

<style>
[x-cloak] { display: none !important; }
.opening-check-item {
    padding: 0.9rem 0;
    border-top: 1px solid #e2e8f0;
}
.opening-check-item:first-of-type { border-top: 0; padding-top: 0; }
.opening-check-label { font-weight: 700; margin-bottom: 0.6rem; }
.opening-check-choices {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.opening-check-choice {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
    padding: 0.45rem 0.8rem;
    font-weight: 700;
    font-size: 0.9rem;
}
.opening-check-deviation,
.opening-check-existing {
    margin-top: 0.75rem;
    padding: 0.85rem;
    background: #fff7ed;
    border: 1px solid #fdba74;
    border-radius: 12px;
}
.opening-deviation-row {
    padding: 0.75rem 0;
    border-top: 1px solid #e2e8f0;
}
.opening-deviation-row:first-of-type { border-top: 0; padding-top: 0; }
</style>
@endsection
