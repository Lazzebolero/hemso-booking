@extends('layouts.app')

@section('content')
@php
    $fp = $routePrefix ?? 'admin';
@endphp

<div class="staff-page-stack">
    @include('partials.ui.flash-messages')

    <x-ui.page-header
        :title="'Minne #'.$memory->id"
        :subtitle="$memory->typeLabel().' · '.$memory->created_at?->format('Y-m-d H:i')"
        icon="bi-journal-text"
    >
        <x-slot:actions>
            <a href="{{ route($fp . '.facility-memories.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Tillbaka
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="page-card">
                <div class="section-title mb-3">Innehåll</div>

                @if($memory->isText())
                    <div class="memory-body-text">{{ $memory->body }}</div>
                @else
                    @if(Route::has($fp . '.facility-memories.audio'))
                        <audio controls class="w-100 mb-3" preload="metadata">
                            <source src="{{ route($fp . '.facility-memories.audio', $memory) }}" type="{{ $memory->audio_mime_type ?: 'audio/webm' }}">
                        </audio>
                    @endif

                    @if($memory->formattedAudioDuration())
                        <div class="small-muted mb-3">Längd: {{ $memory->formattedAudioDuration() }}</div>
                    @endif

                    @if($memory->context_note)
                        <div class="section-title mb-2">Kontext</div>
                        <div class="memory-body-text">{{ $memory->context_note }}</div>
                    @endif
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="page-card mb-3">
                <div class="section-title mb-3">Metadata</div>

                <dl class="memory-meta-list">
                    <dt>Status</dt>
                    <dd>{{ $memory->statusLabel() }}</dd>

                    <dt>Typ</dt>
                    <dd>{{ $memory->typeLabel() }}</dd>

                    <dt>Samtycke</dt>
                    <dd>{{ $memory->consentTypeLabel() }}</dd>

                    <dt>Insamlad av</dt>
                    <dd>{{ $memory->collectedBy?->name ?? '—' }}</dd>

                    <dt>Plats</dt>
                    <dd>{{ $memory->location_text ?: '—' }}</dd>

                    <dt>Ungefärlig tid</dt>
                    <dd>{{ $memory->era_text ?: '—' }}</dd>

                    <dt>Besökare</dt>
                    <dd>{{ $memory->visitor_name ?: 'Anonym' }}</dd>

                    <dt>Kopplad tur</dt>
                    <dd>
                        @if($memory->tour)
                            {{ $memory->tour->tourType?->name ?? 'Tur' }}
                            {{ $memory->tour->tour_date?->format('Y-m-d') }}
                            @if(!empty($memory->tour->start_time))
                                {{ substr($memory->tour->start_time, 0, 5) }}
                            @endif
                        @else
                            —
                        @endif
                    </dd>

                    <dt>Granskad</dt>
                    <dd>
                        @if($memory->reviewed_at)
                            {{ $memory->reviewed_at->format('Y-m-d H:i') }}
                            @if($memory->reviewedBy)
                                <span class="small-muted">({{ $memory->reviewedBy->name }})</span>
                            @endif
                        @else
                            —
                        @endif
                    </dd>
                </dl>
            </div>

            @if($canAdministrate ?? false)
            <div class="page-card">
                <div class="section-title mb-3">Hantera</div>

                <form method="POST" action="{{ route($fp . '.facility-memories.update', $memory) }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select" required>
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $memory->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Anteckningar</label>
                        <textarea name="admin_notes" id="admin_notes" class="form-control" rows="4">{{ old('admin_notes', $memory->admin_notes) }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Anledning vid avvisning</label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="3">{{ old('rejection_reason', $memory->rejection_reason) }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Spara ändringar
                    </button>
                </form>
            </div>
            @endif

            @if($canDelete ?? false)
            <div class="page-card mt-3">
                <div class="section-title mb-3">Radera</div>
                <p class="small-muted">Tar bort minnet permanent, t.ex. avvisade eller ointressanta poster.</p>
                <form
                    method="POST"
                    action="{{ route($fp . '.facility-memories.destroy', $memory) }}"
                    onsubmit="return confirm('Radera minnet permanent? Detta går inte att ångra.')"
                >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="bi bi-trash me-1"></i>Radera minne
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
.memory-body-text {
    white-space: pre-wrap;
    line-height: 1.6;
}

.memory-meta-list {
    margin: 0;
}

.memory-meta-list dt {
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    margin-top: 0.75rem;
}

.memory-meta-list dt:first-child {
    margin-top: 0;
}

.memory-meta-list dd {
    margin: 0.15rem 0 0;
}
</style>
@endsection
