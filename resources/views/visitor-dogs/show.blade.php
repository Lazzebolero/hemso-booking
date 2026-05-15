@extends($useGuideLayout ? 'layouts.guide' : 'layouts.app')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
    <div>
        <h2 class="page-title" @if($useGuideLayout) style="font-size: 1.15rem;" @endif>{{ $dog->dog_name }}</h2>
        <p class="page-subtitle mb-0" @if($useGuideLayout) style="font-size: 0.88rem;" @endif>
            Besök {{ $dog->visit_date?->format('Y-m-d') }}
            @if($dog->tour_start_time)
                · Turstart {{ \Illuminate\Support\Str::of((string) $dog->tour_start_time)->substr(0, 5) }}
            @endif
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('visitor-dogs.edit', $dog) }}" class="btn btn-primary btn-sm">Redigera</a>
        <a href="{{ route('visitor-dogs.index', ['from_date' => $dog->visit_date?->format('Y-m-d'), 'to_date' => $dog->visit_date?->format('Y-m-d')]) }}" class="btn btn-outline-secondary btn-sm">Mina hundar</a>
    </div>
</div>

@if($useGuideLayout)
    <div class="guide-card mb-3">
@else
    <div class="page-card mb-3">
@endif
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

@if($useGuideLayout)
    <div class="guide-card">
@else
    <div class="page-card">
@endif
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
        <input type="hidden" name="from_date" value="{{ $dog->visit_date?->format('Y-m-d') }}">
        <input type="hidden" name="to_date" value="{{ $dog->visit_date?->format('Y-m-d') }}">
        <button type="submit" class="btn btn-outline-danger btn-sm">Ta bort registrering</button>
    </form>
</div>
@endsection
