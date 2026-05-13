@props(['status' => 'default'])

@php
    $map = [
        'planned' => ['class' => 'badge-soft badge-soft-info', 'label' => 'Planerad'],
        'started' => ['class' => 'badge-soft badge-soft-warning', 'label' => 'Startad'],
        'completed' => ['class' => 'badge-soft badge-soft-danger', 'label' => 'Avslutad'],
        'cancelled' => ['class' => 'badge-soft badge-soft-default', 'label' => 'Inställd'],
        'default' => ['class' => 'badge-soft badge-soft-default', 'label' => 'Okänd'],
    ];

    $item = $map[$status] ?? $map['default'];
@endphp

<span {{ $attributes->merge(['class' => $item['class']]) }}>
    <i class="bi bi-circle-fill" style="font-size:0.42rem;"></i>
    {{ $item['label'] }}
</span>
