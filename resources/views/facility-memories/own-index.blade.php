@extends($layout)

@section('content')
@if($shell === 'staff')
<div class="staff-page-stack">
    @include('partials.ui.flash-messages')

    <x-ui.page-header
        title="Mina minnen"
        subtitle="Här ser du bara minnen som du själv har sparat."
        icon="bi-journal-text"
    >
        <x-slot:actions>
            @if(Route::has($routePrefix . '.memories.create'))
                <a href="{{ route($routePrefix . '.memories.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Spara nytt minne
                </a>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="page-card">
@else
<div class="guide-page-stack">
    @include('partials.ui.flash-messages')

    <div class="guide-card mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <div class="guide-card-label">Anläggningsminnen</div>
                <h2 class="guide-card-title mb-1">Mina insamlade minnen</h2>
                <p class="text-muted small mb-0">Här ser du bara minnen som du själv har sparat.</p>
            </div>
            @if(Route::has($routePrefix . '.memories.create'))
                <a href="{{ route($routePrefix . '.memories.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Spara nytt minne
                </a>
            @endif
        </div>
    </div>

    <div class="guide-card">
@endif
        @forelse($memories as $memory)
            <a
                href="{{ route($routePrefix . '.memories.show', $memory) }}"
                class="d-block text-decoration-none text-body border-bottom py-3 {{ $loop->last ? 'border-0 pb-0' : '' }}"
            >
                <div class="d-flex justify-content-between gap-3">
                    <div>
                        <div class="fw-semibold">{{ $memory->summaryText(100) }}</div>
                        <div class="small text-muted mt-1">
                            {{ $memory->created_at?->format('Y-m-d H:i') }}
                            · {{ $memory->typeLabel() }}
                            @if($memory->location_text)
                                · {{ $memory->location_text }}
                            @endif
                        </div>
                    </div>
                    <span class="badge bg-secondary align-self-start">{{ $memory->statusLabel() }}</span>
                </div>
            </a>
        @empty
            <p class="text-muted mb-0">Du har inte sparat några minnen ännu.</p>
        @endforelse

        @if($memories->hasPages())
            <div class="mt-3">
                {{ $memories->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
