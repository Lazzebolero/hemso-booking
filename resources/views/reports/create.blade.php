@extends('layouts.app')
@include('guide.partials.topbar')
@section('content')
<div class="guide-page-header mb-4">
    <div>
        <h2 class="mb-1">Ny felrapport</h2>
        <div class="muted">Rapportera fel eller brister direkt från mobilen.</div>
    </div>

    <a href="{{ route('guide.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-2"></i>Tillbaka
    </a>
</div>

<div class="guide-form-card">
    <form method="POST" action="{{ route('guide.reports.store') }}" enctype="multipart/form-data" class="row g-3">
        @csrf

        <div class="col-12">
            <label class="form-label">Rubrik</label>
            <input
                type="text"
                name="title"
                class="form-control form-control-lg"
                value="{{ old('title') }}"
                placeholder="Kort beskrivning av felet"
                required
                autofocus
            >
        </div>

        <div class="col-md-6">
            <label class="form-label">Kategori</label>
            <select name="category" class="form-select form-select-lg" required>
                <option value="">Välj kategori</option>
                @foreach($categories as $category)
                    <option value="{{ $category->name }}" @selected(old('category') === $category->name)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Prioritet</label>
            <select name="priority" class="form-select form-select-lg" required>
                <option value="">Välj prioritet</option>
                @foreach($priorities as $priority)
                    <option value="{{ $priority->name }}" @selected(old('priority') === $priority->name)>
                        {{ $priority->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Plats</label>
            <input
                type="text"
                name="location"
                class="form-control form-control-lg"
                value="{{ old('location') }}"
                placeholder="Exempel: Entré, toalett, utställning"
            >
        </div>

        <div class="col-12">
            <label class="form-label">Beskrivning</label>
            <textarea
                name="description"
                class="form-control form-control-lg"
                rows="6"
                placeholder="Beskriv vad som är fel och om något behöver åtgärdas direkt"
                required
            >{{ old('description') }}</textarea>
        </div>

        <div class="col-12">
            <label class="form-label">Bild (valfri)</label>
            <input
                type="file"
                name="image"
                class="form-control form-control-lg"
                accept="image/*"
            >
            <div class="form-text">JPG, PNG eller WebP. Max 5 MB.</div>
        </div>

        <div class="col-12">
            <div class="guide-info-box">
                <div class="fw-semibold mb-1">
                    <i class="bi bi-info-circle me-2"></i>Tips
                </div>
                <div class="small">
                    Om felet påverkar säkerhet eller dagens visningar, välj hög prioritet och lägg gärna till bild.
                </div>
            </div>
        </div>

        <div class="col-12 d-grid gap-2">
            <button class="btn btn-primary btn-lg" type="submit">
                <i class="bi bi-send-check me-2"></i>Skicka felrapport
            </button>

            <a href="{{ route('guide.dashboard') }}" class="btn btn-outline-secondary">
                Avbryt
            </a>
        </div>
    </form>
</div>

<style>
.guide-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    flex-wrap: wrap;
}

.guide-form-card {
    background: rgba(255,255,255,0.96);
    border-radius: 22px;
    box-shadow: var(--shadow-soft);
    border: 1px solid rgba(255,255,255,0.75);
    padding: 1rem;
    max-width: 780px;
}

.guide-info-box {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1e3a8a;
    border-radius: 16px;
    padding: 0.9rem 1rem;
}

@media (max-width: 576px) {
    .content-area {
        padding: 1rem 0.85rem 5.5rem;
    }

    .guide-page-header {
        flex-direction: column;
        align-items: stretch;
    }

    .guide-form-card {
        padding: 0.9rem;
        border-radius: 18px;
    }
}
</style>
@endsection