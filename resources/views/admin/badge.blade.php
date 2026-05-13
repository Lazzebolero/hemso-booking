@props([
    'variant' => 'secondary',
])

<span {{ $attributes->merge(['class' => 'badge-soft badge-soft-' . $variant]) }}>
    {{ $slot }}
</span>