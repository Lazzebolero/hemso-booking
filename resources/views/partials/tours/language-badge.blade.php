@php
    $codes = collect($tour->language_codes ?? [])
        ->filter()
        ->map(fn ($code) => strtoupper((string) $code))
        ->unique()
        ->values();
@endphp

@if($codes->isNotEmpty())
    @if($codes->count() === 1)
        <span class="badge-soft badge-soft-secondary" title="Bokade språk">Språk: {{ $codes->first() }}</span>
    @else
        <span class="badge-soft badge-soft-danger" title="Bokade språk">Språk: {{ $codes->implode(' + ') }}</span>
    @endif
@endif
