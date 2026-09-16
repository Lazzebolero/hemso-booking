@extends('layouts.app')

@section('content')
@php
    $prefix = $prefix ?? \App\Support\ActiveRole::routePrefix();
@endphp
<div class="page-header">
    <div>
        <h2 class="page-title">Öppningskontroll {{ $check->check_date?->format('Y-m-d') }}</h2>
        <div class="page-subtitle">Daglig säkerhetsrutin för guider och öppningsansvarig – Hemsö Fästning</div>
    </div>
    <div class="page-actions">
        <a href="{{ route($prefix.'.opening-checks.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Alla kontroller
        </a>
    </div>
</div>

@include('partials.ui.flash-messages')

<div class="page-card mb-4">
    <div class="section-title">Protokoll</div>
    <dl class="row mb-0">
        <dt class="col-sm-4">Öppningsansvarig</dt>
        <dd class="col-sm-8">{{ $check->openedBy?->name ?? '-' }}</dd>
        <dt class="col-sm-4">Påbörjad</dt>
        <dd class="col-sm-8">{{ $check->started_at?->format('Y-m-d H:i') ?? '-' }}</dd>
        <dt class="col-sm-4">Avslutad</dt>
        <dd class="col-sm-8">{{ $check->completed_at?->format('Y-m-d H:i') ?? '-' }}</dd>
        <dt class="col-sm-4">Anläggningen öppnas kl</dt>
        <dd class="col-sm-8">{{ $check->visitorOpensAtInput() ?? '-' }}</dd>
        <dt class="col-sm-4">Signerad</dt>
        <dd class="col-sm-8">{{ $check->confirmed ? 'Ja' : 'Nej' }}</dd>
        <dt class="col-sm-4">Status</dt>
        <dd class="col-sm-8">{{ $check->statusLabel() }}</dd>
    </dl>
</div>

<div class="page-card mb-4">
    <div class="section-title">Kontrollpunkter</div>
    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Punkt</th>
                    <th style="width: 160px;">Utfall</th>
                </tr>
            </thead>
            <tbody>
                @foreach($checkpoints as $key => $label)
                    @php $outcome = $check->outcomeFor($key); @endphp
                    <tr>
                        <td>{{ $label }}</td>
                        <td>
                            @if($outcome)
                                <span class="badge-soft {{ $outcome === 'deviation' ? 'badge-soft-warning' : 'badge-soft-success' }}">
                                    {{ \App\Support\OpeningCheckpoints::outcomeLabel($outcome) }}
                                </span>
                            @else
                                <span class="small-muted">Ej ifylld</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="page-card">
    <div class="section-title">Avvikelserapporter</div>

    @forelse($check->deviations as $deviation)
        <div class="border rounded-3 p-3 mb-3">
            <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                <div>
                    <div class="fw-semibold">{{ $deviation->checkpointLabel() }}</div>
                    <div class="small-muted">
                        {{ $deviation->occurred_at?->format('Y-m-d H:i') }}
                        · {{ $deviation->reporter?->name ?? '-' }}
                    </div>
                </div>
                <span class="badge-soft {{ $deviation->isResolved() ? 'badge-soft-success' : 'badge-soft-warning' }}">
                    {{ $deviation->statusLabel() }}
                </span>
            </div>

            <dl class="row mb-0">
                <dt class="col-sm-4">Plats / nödutgång</dt>
                <dd class="col-sm-8">{{ $deviation->location ?: '-' }}</dd>
                <dt class="col-sm-4">Beskrivning</dt>
                <dd class="col-sm-8" style="white-space: pre-wrap;">{{ $deviation->description }}</dd>
                <dt class="col-sm-4">Omedelbar åtgärd</dt>
                <dd class="col-sm-8" style="white-space: pre-wrap;">{{ $deviation->immediate_action ?: '-' }}</dd>
                <dt class="col-sm-4">Vem informerades</dt>
                <dd class="col-sm-8">{{ $deviation->informed_person ?: '-' }}</dd>
                <dt class="col-sm-4">Beslut innan öppning</dt>
                <dd class="col-sm-8" style="white-space: pre-wrap;">{{ $deviation->decision_before_opening ?: '-' }}</dd>
                @if($deviation->isResolved())
                    <dt class="col-sm-4">Åtgärdad av</dt>
                    <dd class="col-sm-8">
                        {{ $deviation->resolver?->name ?? '-' }}
                        @if($deviation->resolved_at)
                            · {{ $deviation->resolved_at->format('Y-m-d H:i') }}
                        @endif
                    </dd>
                    <dt class="col-sm-4">Åtgärdsnotering</dt>
                    <dd class="col-sm-8" style="white-space: pre-wrap;">{{ $deviation->resolution_note ?: '-' }}</dd>
                @endif
            </dl>

            @if($deviation->isOpen())
                <form method="POST" action="{{ route($prefix.'.opening-checks.deviations.resolve', [$check, $deviation]) }}" class="mt-3">
                    @csrf
                    @method('PATCH')
                    <label class="form-label" for="resolution_note_{{ $deviation->id }}">Notering vid åtgärd</label>
                    <textarea
                        id="resolution_note_{{ $deviation->id }}"
                        name="resolution_note"
                        class="form-control mb-2"
                        rows="2"
                    ></textarea>
                    <button type="submit" class="btn btn-primary">Märk som åtgärdad</button>
                </form>
            @endif
        </div>
    @empty
        <div class="small-muted">Inga avvikelser för den här dagen.</div>
    @endforelse
</div>
@endsection
