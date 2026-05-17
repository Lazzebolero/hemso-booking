@extends('layouts.guide')

@section('content')
<div class="page-card mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h2 class="page-title mb-1">Ny felrapport</h2>
            <div class="page-subtitle">
                Rapportera fel och avvikelser snabbt och tydligt direkt från guidevyn.
            </div>
        </div>

        <a href="{{ route('guide.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<form method="POST" action="{{ route('guide.reports.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="guide-report-layout">
        <div class="guide-focus-card">
            <div class="section-title">Grunduppgifter</div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Rubrik</label>
                    <input
                        type="text"
                        name="title"
                        class="form-control"
                        value="{{ old('title') }}"
                        placeholder="Kort rubrik för problemet"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Välj kategori</option>
                        @foreach(($categories ?? collect()) as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Klassning</label>
                    <select name="priority_id" class="form-select" required>
                        <option value="">Välj klassning</option>
                        @foreach(($priorities ?? collect()) as $priority)
                            <option value="{{ $priority->id }}" @selected((string) old('priority_id') === (string) $priority->id)>
                                {{ $priority->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Plats</label>
                    <select name="location_id" class="form-select">
                        <option value="">Välj plats</option>
                        @foreach(($locations ?? collect()) as $location)
                            <option value="{{ $location->id }}" @selected((string) old('location_id') === (string) $location->id)>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Fri platsbeskrivning</label>
                    <input
                        type="text"
                        name="location_text"
                        class="form-control"
                        value="{{ old('location_text') }}"
                        placeholder="Om platsen inte finns i listan"
                    >
                </div>

                <div class="col-12">
                    <label class="form-label">Bild (valfritt)</label>
                    <input
                        type="file"
                        name="attachment"
                        class="form-control"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        capture="environment"
                    >
                    <div class="form-text">JPG, PNG, GIF, WebP eller HEIC (iPhone). Högst 10 MB. På mobil kan du ta foto direkt.</div>
                </div>

                <div class="col-12">
                    <label class="form-label">Beskrivning</label>
                    <textarea
                        name="description"
                        class="form-control guide-textarea-xl"
                        rows="10"
                        placeholder="Beskriv vad som hänt, var problemet finns och hur allvarligt det är."
                        required
                    >{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <div class="page-card guide-side-panel">
            <div class="section-title">Skicka rapport</div>

            <div class="info-item mb-3">
                <div class="fw-semibold mb-1">Rapporten går direkt till admin</div>
                <div class="small-muted">
                    Använd tydlig rubrik, välj rätt kategori och beskriv platsen så konkret som möjligt.
                </div>
            </div>

            <div class="info-item mb-3">
                <div class="small-muted mb-1">Inloggad användare</div>
                <div class="fw-semibold">{{ auth()->user()->name }}</div>
            </div>

            <div class="guide-primary-actions">
                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="bi bi-send-check me-2"></i>Skapa felrapport
                </button>

                <a href="{{ route('guide.dashboard') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-circle me-2"></i>Avbryt
                </a>
            </div>
        </div>
    </div>
</form>

<style>
.guide-report-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) 320px;
    gap: 1rem;
    align-items: start;
}

.guide-side-panel {
    align-self: start;
}

.guide-textarea-xl {
    min-height: 260px !important;
}

@media (max-width: 1100px) {
    .guide-report-layout {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection