@extends($useGuideLayout ? 'layouts.guide' : 'layouts.app')

@section('content')
@php
    $visitSubtitle = 'Besök '.$dog->visit_date?->format('Y-m-d');
    if ($dog->tour_start_time) {
        $visitSubtitle .= ' · Turstart '.\Illuminate\Support\Str::of((string) $dog->tour_start_time)->substr(0, 5);
    }
    $cardClass = $useGuideLayout ? 'guide-card' : 'page-card';
@endphp

<div class="staff-page-stack">
    <x-ui.page-header
        :guide="$useGuideLayout"
        :title="$dog->dog_name"
        :subtitle="$visitSubtitle"
        icon="bi-heart-pulse"
    >
        <x-slot:actions>
            <a href="{{ \App\Support\VisitorDogSupport::routeForDog('visitor-dogs.edit', $dog, $navQuery ?? []) }}" class="btn btn-primary{{ $useGuideLayout ? ' btn-sm' : '' }}">
                <i class="bi bi-pencil me-1"></i>Redigera
            </a>
            <a href="{{ $backNav['url'] ?? route('visitor-dogs.index') }}" class="btn btn-outline-secondary{{ $useGuideLayout ? ' btn-sm' : '' }}">
                <i class="bi bi-arrow-left me-1"></i>{{ $backNav['label'] ?? 'Mina hundar' }}
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    @include('partials.ui.flash-messages', ['guide' => $useGuideLayout])

    <div class="{{ $cardClass }}">
        <div class="section-title mb-3">Uppgifter</div>
        <dl class="row mb-0">
            <dt class="col-sm-4 small-muted">Namn</dt>
            <dd class="col-sm-8 fw-semibold">{{ $dog->dog_name }}</dd>
            <dt class="col-sm-4 small-muted">Ras</dt>
            <dd class="col-sm-8">{{ $dog->breed ?: '—' }}</dd>
            <dt class="col-sm-4 small-muted">Ägarens telefon</dt>
            <dd class="col-sm-8">{{ $dog->owner_phone ?: '—' }}</dd>
            <dt class="col-sm-4 small-muted">Datum</dt>
            <dd class="col-sm-8">{{ $dog->visit_date?->format('Y-m-d') }}</dd>
            <dt class="col-sm-4 small-muted">Turstart</dt>
            <dd class="col-sm-8">
                @if($dog->tour_start_time)
                    {{ \Illuminate\Support\Str::of((string) $dog->tour_start_time)->substr(0, 5) }}
                @else
                    —
                @endif
            </dd>
        </dl>
    </div>

    <div class="{{ $cardClass }}">
        <div class="section-title mb-3">Bild</div>
        @if($dog->photo_path)
            <img src="{{ route('visitor-dogs.photo', $dog) }}"
                 alt="Bild på {{ $dog->dog_name }}"
                 class="img-fluid rounded"
                 style="max-height: 420px; object-fit: contain;">
        @else
            <p class="small-muted mb-0">Ingen bild bifogad.</p>
        @endif

        <form method="POST"
              action="{{ route('visitor-dogs.destroy', $dog) }}"
              class="mt-4"
              onsubmit="return confirm('Ta bort denna registrering?')">
            @csrf
            @method('DELETE')
            @include('partials.visitor-dogs.navigation-hidden-fields', ['navigationQuery' => $navQuery ?? []])
            <button type="submit" class="btn btn-outline-danger btn-sm">Ta bort registrering</button>
        </form>
    </div>
</div>
@endsection
