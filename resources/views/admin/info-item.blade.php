@props([
    'label',
])

<div class="info-item">
    <div class="info-label">{{ $label }}</div>
    <div class="info-value">{{ $slot }}</div>
</div>