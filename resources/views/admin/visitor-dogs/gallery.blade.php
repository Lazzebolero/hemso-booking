@extends('layouts.app')

@section('content')
@php
    $vPrefix = $visitorDogsRoutePrefix ?? 'admin';
@endphp
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
        <h2 class="page-title">Hundbilder</h2>
        <div class="page-subtitle">
            Registreringar med foto i valt datumintervall. Klicka på en bild för större vy — piltangenter byter bild, Esc stänger.
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

<div class="page-card"
     x-data="{
        items: {{ $lightboxItems }},
        open: false,
        idx: 0,
        openAt(i) {
            this.idx = i;
            this.open = true;
        },
        close() {
            this.open = false;
        },
        prev() {
            if (this.items.length === 0) {
                return;
            }
            this.idx = (this.idx + this.items.length - 1) % this.items.length;
        },
        next() {
            if (this.items.length === 0) {
                return;
            }
            this.idx = (this.idx + 1) % this.items.length;
        },
        destroy() {
            document.documentElement.classList.remove('visitor-dog-gallery-scroll-lock');
            document.body.classList.remove('visitor-dog-gallery-scroll-lock');
        },
     }"
     x-init="$watch('open', (value) => {
        document.documentElement.classList.toggle('visitor-dog-gallery-scroll-lock', value);
        document.body.classList.toggle('visitor-dog-gallery-scroll-lock', value);
     })"
     @keydown.window="if (!open) { return; }
        if ($event.key === 'Escape') { close(); }
        else if ($event.key === 'ArrowLeft') { prev(); $event.preventDefault(); }
        else if ($event.key === 'ArrowRight') { next(); $event.preventDefault(); }">
    @if($dogs->isEmpty())
        <p class="text-muted mb-0">Inga hundbilder i valt intervall.</p>
    @else
        <div class="visitor-dog-gallery-grid mx-auto">
            @foreach($dogs as $dog)
                <button type="button"
                        class="visitor-dog-gallery-tile btn border-0 p-0 text-start bg-transparent w-100"
                        @click="openAt({{ $loop->index }})">
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
                </button>
            @endforeach
        </div>

        <div class="mt-3">
            {{ $dogs->links() }}
        </div>
    @endif

    <template x-teleport="body">
        <div x-show="open"
             x-cloak
             class="visitor-dog-lightbox position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
             style="z-index: 1080; background: rgba(15, 23, 42, 0.88);"
             @click.self="close()"
             x-transition.opacity.duration.200ms>
            <div class="visitor-dog-lightbox-inner position-relative w-100 h-100 d-flex flex-column p-2 p-md-4"
                 style="max-width: 1200px; max-height: 100vh;">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2 flex-shrink-0">
                    <div class="text-white text-truncate small" x-show="items.length" x-text="items[idx]?.name + ' · ' + (items[idx]?.date || '')"></div>
                    <button type="button"
                            class="btn btn-sm btn-light"
                            @click="close()"
                            aria-label="Stäng">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="flex-grow-1 d-flex align-items-center justify-content-center position-relative overflow-hidden rounded-3 bg-black bg-opacity-25">
                    <template x-if="items.length">
                        <img :src="items[idx]?.photo"
                             :alt="items[idx]?.name"
                             class="visitor-dog-lightbox-img rounded-3 border border-secondary border-opacity-25 shadow-lg">
                    </template>
                    <button type="button"
                            class="btn btn-light btn-sm position-absolute top-50 start-0 translate-middle-y ms-1 d-none d-md-inline-flex"
                            @click.stop="prev()"
                            aria-label="Föregående bild">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button type="button"
                            class="btn btn-light btn-sm position-absolute top-50 end-0 translate-middle-y me-1 d-none d-md-inline-flex"
                            @click.stop="next()"
                            aria-label="Nästa bild">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 flex-shrink-0">
                    <div class="small text-white-50 text-truncate" x-show="items.length" x-text="items[idx]?.breed ? ('Ras: ' + items[idx].breed) : ''"></div>
                    <a :href="items[idx]?.show"
                       class="btn btn-sm btn-outline-light"
                       x-show="items.length"
                       @click="close()">
                        Öppna detaljer
                    </a>
                </div>
            </div>
        </div>
    </template>
</div>

<style>
[x-cloak] {
    display: none !important;
}
html.visitor-dog-gallery-scroll-lock,
body.visitor-dog-gallery-scroll-lock {
    overflow: hidden !important;
}
.visitor-dog-gallery-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.55rem 0.6rem;
    max-width: 30rem;
}
@media (min-width: 400px) {
    .visitor-dog-gallery-grid {
        max-width: 36rem;
    }
}
@media (min-width: 576px) {
    .visitor-dog-gallery-grid {
        max-width: 42rem;
    }
}
.visitor-dog-gallery-tile.btn {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    justify-content: flex-start;
    width: 100%;
    min-width: 0;
    text-align: start;
}
.visitor-dog-gallery-thumb {
    aspect-ratio: 4 / 3;
    position: relative;
    width: 100%;
    min-height: 0;
    flex-shrink: 0;
    background: var(--surface-muted, #f1f5f9);
}
.visitor-dog-gallery-tile:focus-visible {
    outline: 2px solid var(--bs-primary, #0d6efd);
    outline-offset: 2px;
    border-radius: 0.35rem;
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
.visitor-dog-lightbox-img {
    max-width: 100%;
    max-height: min(68vh, 820px);
    width: auto;
    height: auto;
    object-fit: contain;
}
</style>
@endsection
