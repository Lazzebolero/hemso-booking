@props([
    'tour',
    'class' => 'small-muted',
])

@php
    $coGuideLine = app(\App\Services\TourCoGuideService::class)->coGuideSummary($tour);
@endphp

@if(filled($coGuideLine))
    <div {{ $attributes->merge(['class' => $class]) }}>Med: {{ $coGuideLine }}</div>
@endif
