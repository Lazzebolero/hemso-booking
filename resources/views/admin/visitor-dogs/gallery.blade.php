@extends('layouts.app')

@section('content')
@php
    $vPrefix = $visitorDogsRoutePrefix ?? 'admin';
@endphp
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
        <h2 class="page-title">Hundbilder</h2>
        <div class="page-subtitle">
            Registreringar med foto i valt datumintervall. Klicka på en bild eller namn för att öppna detaljsidan.
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route($vPrefix . '.visitor-dogs.index', request()->only(['from_date', 'to_date'])) }}" class="btn btn-outline-secondary">
            <i class="bi bi-list-ul me-1"></i>Lista
        </a>
    </div>
</div>

<div class="page-card mb-4">
    <form method="GET" action="{{ route($vPrefix . '.visitor-dogs.gallery') }}" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Från datum</label>
            <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Till datum</label>
            <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
        </div>
        <div class="col-md-6">
            <button type="submit" class="btn btn-primary me-2">
                <i class="bi bi-funnel me-1"></i>Visa
            </button>
            <a href="{{ route($vPrefix . '.visitor-dogs.gallery') }}" class="btn btn-outline-secondary">Idag</a>
        </div>
    </form>
</div>

<div class="page-card">
    @if($dogs->isEmpty())
        <p class="text-muted mb-0">Inga hundbilder i valt intervall.</p>
    @else
        <div class="visitor-dog-gallery-grid">
            @foreach($dogs as $dog)
                <a href="{{ route($vPrefix . '.visitor-dogs.show', $dog) }}"
                   class="visitor-dog-gallery-tile">
                    <div class="visitor-dog-gallery-thumb rounded-2 overflow-hidden border shadow-sm">
                        <img src="{{ route($vPrefix . '.visitor-dogs.photo', $dog) }}"
                             alt="{{ $dog->dog_name }}"
                             class="visitor-dog-gallery-tile-img"
                             loading="lazy"
                             width="240"
                             height="180">
                    </div>
                    <div class="visitor-dog-gallery-caption mt-1">
                        <div class="visitor-dog-gallery-name text-truncate">{{ $dog->dog_name }}</div>
                        <div class="visitor-dog-gallery-date text-truncate">{{ $dog->visit_date?->format('Y-m-d') }}</div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-3">
            {{ $dogs->links() }}
        </div>
    @endif
</div>

<style>
.visitor-dog-gallery-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.85rem 1rem;
    width: 100%;
    max-width: 100%;
}
@media (min-width: 900px) {
    .visitor-dog-gallery-grid {
        gap: 1rem 1.15rem;
    }
}
.visitor-dog-gallery-tile {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    justify-content: flex-start;
    width: 100%;
    min-width: 0;
    text-align: start;
    text-decoration: none;
    color: inherit;
    border-radius: 0.35rem;
}
.visitor-dog-gallery-tile:hover .visitor-dog-gallery-name {
    text-decoration: underline;
}
.visitor-dog-gallery-tile:focus-visible {
    outline: 2px solid var(--bs-primary, #0d6efd);
    outline-offset: 3px;
}
.visitor-dog-gallery-thumb {
    aspect-ratio: 4 / 3;
    position: relative;
    width: 100%;
    min-height: 0;
    flex-shrink: 0;
    background: var(--surface-muted, #f1f5f9);
}
.visitor-dog-gallery-tile-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.visitor-dog-gallery-name {
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1.25;
    color: var(--bs-body-color, #212529);
}
.visitor-dog-gallery-date {
    font-size: 0.75rem;
    line-height: 1.3;
    color: var(--bs-secondary-color, #6c757d);
}
</style>
@endsection
