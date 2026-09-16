@php
    $departure = $departure ?? [];
@endphp
@if($departure['requires_call'] ?? false)
    <div class="small-muted">Kallelsetur</div>
@endif
@if($departure['no_duplicates'] ?? false)
    <div class="small-muted">Dubbleringsturer: Nej</div>
@endif
