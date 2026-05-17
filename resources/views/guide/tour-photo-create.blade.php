@extends('layouts.guide')

@section('content')
<div class="guide-card mb-3">
    <div class="page-header mb-0 pb-3" style="border-bottom: 1px solid var(--brand-line-soft);">
        <div>
            <h2 class="page-title" style="font-size: 1.15rem;">Ladda upp turbild</h2>
            <p class="page-subtitle mb-0" style="font-size: 0.88rem;">
                {{ $tour->tourType?->name ?? 'Tur' }} {{ $tour->starts_at?->format('Y-m-d H:i') }}
            </p>
        </div>
    </div>
</div>

<div class="guide-card">
    @include('partials.ui.flash-messages', ['guide' => true])

    <form method="POST" action="{{ route('guide.tours.photos.store', $tour, false) }}" enctype="multipart/form-data">
        @csrf

        <div class="mb-4">
            <label for="photo" class="form-label fw-semibold">Bild</label>
            <input
                type="file"
                name="photo"
                id="photo"
                class="form-control"
                accept="image/jpeg,image/png,image/gif,image/webp,image/heic,image/heif,.heic,.heif"
                capture="environment"
                required
            >
            <div class="form-text">Max 10 MB. På mobil kan du ta foto direkt.</div>

            @error('photo')
                <div class="text-danger mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="caption" class="form-label fw-semibold">Bildtext (valfritt)</label>
            <input
                type="text"
                name="caption"
                id="caption"
                class="form-control"
                maxlength="255"
                value="{{ old('caption') }}"
                placeholder="Ex. Företagsgrupp vid kanonen"
            >

            @error('caption')
                <div class="text-danger mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                <i class="bi bi-camera me-2"></i>Ladda upp bild
            </button>
            <a href="{{ route('guide.tours.show', $tour, false) }}" class="btn btn-outline-secondary btn-lg">Avbryt</a>
        </div>
    </form>
</div>
@endsection
