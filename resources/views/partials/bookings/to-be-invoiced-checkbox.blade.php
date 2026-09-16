@props([
    'booking' => null,
    'columnClass' => 'col-md-4',
    'checkboxId' => 'to_be_invoiced',
])

@php
    $toBeInvoiced = (bool) old('to_be_invoiced', $booking?->to_be_invoiced ?? false);
@endphp

<div class="{{ $columnClass }}">
    <label class="form-label d-block">Fakturering</label>
    <div class="form-check mt-2">
        <input
            class="form-check-input"
            type="checkbox"
            name="to_be_invoiced"
            value="1"
            id="{{ $checkboxId }}"
            @checked($toBeInvoiced)
        >
        <label class="form-check-label" for="{{ $checkboxId }}">Faktureras</label>
    </div>
    <div class="form-text">Ekonomi får e-post när bokningen skapas med detta val.</div>
</div>
