@props([
    'tour',
    'metaClass' => 'small-muted',
])

<div class="{{ $metaClass }} fw-semibold">{{ $tour->guide?->name ?? 'Ej tilldelad' }}</div>

@include('partials.admin.tour-co-guides-line', ['tour' => $tour, 'class' => $metaClass])
