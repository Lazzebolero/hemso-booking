@php
    $flags = $dog->care_flags ?? [];
    $showEmpty = $showEmpty ?? true;
    $labels = [];

    $definitions = [
        'food_allergies' => 'Matallergier',
        'needs_alone' => 'Bör vara ensam',
    ];
    if (class_exists(\App\Support\VisitorDogCareFlags::class)) {
        $labels = \App\Support\VisitorDogCareFlags::selectedLabels(is_array($flags) ? $flags : null);
    } else {
        foreach ($definitions as $key => $label) {
            if ((bool) ($flags[$key] ?? false)) {
                $labels[] = $label;
            }
        }
    }
@endphp

@if($labels === [])
    @if($showEmpty)
        —
    @endif
@else
    <div class="d-flex flex-wrap gap-1{{ $showEmpty ? '' : ' mt-1' }}">
        @foreach($labels as $label)
            <span class="badge-soft badge-soft-warning">{{ $label }}</span>
        @endforeach
    </div>
@endif
