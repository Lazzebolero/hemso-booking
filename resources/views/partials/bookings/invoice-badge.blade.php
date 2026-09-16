@if($booking->to_be_invoiced ?? false)
    <span class="badge-soft badge-soft-info" title="Bokningen ska faktureras">
        <i class="bi bi-receipt me-1" aria-hidden="true"></i>Faktureras
    </span>
@endif
