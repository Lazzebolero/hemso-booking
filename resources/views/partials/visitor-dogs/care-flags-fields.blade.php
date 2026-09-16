@php
    $dog = $dog ?? null;
    $careFlags = old('care_flags', $dog?->care_flags ?? []);
    if (! is_array($careFlags)) {
        $careFlags = [];
    }

    $definitions = [
        'food_allergies' => 'Matallergier',
        'needs_alone' => 'Bör vara ensam',
    ];
    if (class_exists(\App\Support\VisitorDogCareFlags::class)) {
        $definitions = \App\Support\VisitorDogCareFlags::definitions();
    }
@endphp

<div class="mb-4">
    <input type="hidden" name="care_flags_submitted" value="1">
    <div class="form-label fw-semibold mb-2">Särskilda behov</div>
    <div class="d-flex flex-column gap-2">
        @foreach($definitions as $key => $label)
            @php $isChecked = (bool) ($careFlags[$key] ?? false); @endphp
            <div class="form-check">
                <input
                    type="checkbox"
                    class="form-check-input"
                    name="care_flags[{{ $key }}]"
                    id="care_flag_{{ $key }}"
                    value="1"
                    @checked($isChecked)
                >
                <label class="form-check-label" for="care_flag_{{ $key }}">{{ $label }}</label>
            </div>
        @endforeach
    </div>
    <div class="form-text">Valfritt — kryssa i det som gäller.</div>
</div>
