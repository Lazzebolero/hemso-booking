@props([
    'tour',
    'variant' => 'cell',
])

@php
    $summary = $tour->display_co_guide_summary
        ?? app(\App\Services\TourCoGuideService::class)->coGuideSummary($tour);
    $coGuideLine = filled($summary) ? $summary : null;
@endphp

@if($variant === 'inline')
    <span>{{ $tour->guide?->name ?? 'Ej tilldelad' }}</span>
    @if($coGuideLine)
        <br><span>Med: {{ $coGuideLine }}</span>
    @endif
@else
    <div class="fw-semibold">{{ $tour->guide?->name ?? 'Ej tilldelad' }}</div>
    @if($coGuideLine)
        <div class="small-muted">Med: {{ $coGuideLine }}</div>
    @endif
@endif
