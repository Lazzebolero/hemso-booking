@props([
    'booking' => null,
    'columnClass' => 'col-md-4',
    'defaultIncludesMeal' => null,
])

@php
    $fallback = $defaultIncludesMeal;

    if ($fallback === null && $booking?->exists) {
        $fallback = (bool) $booking->includes_meal;
    } elseif ($fallback === null) {
        $fallback = (bool) ($booking?->tour?->default_includes_meal ?? false);
    }

    $includesMeal = (bool) old('includes_meal', $fallback);
@endphp

<div class="{{ $columnClass }}">
    <label class="form-label">Mat</label>
    <select name="includes_meal" class="form-select">
        <option value="0" @selected(! $includesMeal)>Ej mat</option>
        <option value="1" @selected($includesMeal)>Med mat</option>
    </select>
    <div class="form-text">Standard är utan mat om inget annat valts på turen.</div>
</div>
