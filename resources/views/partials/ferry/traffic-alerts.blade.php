@props([
    'alerts' => [],
    'compact' => false,
])

@if(collect($alerts)->isNotEmpty())
    <div @class(['ferry-traffic-alerts', 'ferry-traffic-alerts-compact' => (bool) $compact])>
        @foreach($alerts as $alert)
            @php
                $level = $alert['level'] ?? 'warning';
                $alertClass = match ($level) {
                    'danger' => 'alert-danger',
                    'info' => 'alert-info',
                    default => 'alert-warning',
                };
            @endphp

            <div class="alert {{ $alertClass }} py-2 mb-2">
                <div class="fw-semibold">{{ $alert['title'] ?? 'Trafikinformation' }}</div>
                @if(!empty($alert['message']))
                    <div class="small">{{ $alert['message'] }}</div>
                @endif
            </div>
        @endforeach
    </div>
@endif

<style>
.ferry-traffic-alerts .alert:last-child {
    margin-bottom: 0 !important;
}

.ferry-traffic-alerts-compact .alert {
    font-size: 0.88rem;
}
</style>
