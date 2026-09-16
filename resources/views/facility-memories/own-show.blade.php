@extends($layout)

@section('content')
@if($shell === 'staff')
<div class="staff-page-stack">
    @include('partials.ui.flash-messages')

    <x-ui.page-header
        :title="$memory->typeLabel().' · '.$memory->created_at?->format('Y-m-d H:i')"
        :subtitle="'Status: '.$memory->statusLabel()"
        icon="bi-journal-text"
    >
        <x-slot:actions>
            <a href="{{ route($routePrefix . '.memories.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Mina minnen
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="page-card mb-3">
@else
<div class="guide-page-stack">
    @include('partials.ui.flash-messages')

    <div class="guide-card mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <div class="guide-card-label">Anläggningsminne</div>
                <h2 class="guide-card-title mb-1">{{ $memory->typeLabel() }} · {{ $memory->created_at?->format('Y-m-d H:i') }}</h2>
                <p class="text-muted small mb-0">Status: {{ $memory->statusLabel() }}</p>
            </div>
            <a href="{{ route($routePrefix . '.memories.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Mina minnen
            </a>
        </div>
    </div>

    <div class="guide-card mb-3">
@endif
        @if($memory->isText())
            <div class="memory-body-text">{{ $memory->body }}</div>
        @else
            @if(Route::has($routePrefix . '.memories.audio'))
                <audio controls class="w-100 mb-3" preload="metadata">
                    <source src="{{ route($routePrefix . '.memories.audio', $memory) }}" type="{{ $memory->audio_mime_type ?: 'audio/webm' }}">
                </audio>
            @endif

            @if($memory->context_note)
                @if($shell === 'staff')
                    <div class="section-title mb-2">Kontext</div>
                @else
                    <div class="small text-muted mb-2">Kontext</div>
                @endif
                <div class="memory-body-text">{{ $memory->context_note }}</div>
            @endif
        @endif
    </div>

@if($shell === 'staff')
    <div class="page-card">
        <dl class="memory-meta-list mb-0">
@else
    <div class="guide-card">
        <dl class="mb-0">
@endif
            @if($memory->location_text)
                <dt @class(['small text-muted' => $shell !== 'staff'])>Plats</dt>
                <dd>{{ $memory->location_text }}</dd>
            @endif
            @if($memory->era_text)
                <dt @class(['small text-muted' => $shell !== 'staff'])>Ungefärlig tid</dt>
                <dd>{{ $memory->era_text }}</dd>
            @endif
            @if($memory->visitor_name)
                <dt @class(['small text-muted' => $shell !== 'staff'])>Besökare</dt>
                <dd>{{ $memory->visitor_name }}</dd>
            @endif
            @if($memory->tour)
                <dt @class(['small text-muted' => $shell !== 'staff'])>Tur</dt>
                <dd>
                    {{ $memory->tour->tourType?->name ?? 'Tur' }}
                    {{ $memory->tour->tour_date?->format('Y-m-d') }}
                </dd>
            @endif
        </dl>
    </div>
</div>

@if($shell === 'staff')
<style>
.memory-body-text {
    white-space: pre-wrap;
    line-height: 1.6;
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
@else
<style>
.memory-body-text {
    white-space: pre-wrap;
    line-height: 1.6;
}
</style>
@endif
@endsection
