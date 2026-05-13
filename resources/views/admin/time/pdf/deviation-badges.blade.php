@php
    $deviations = $deviations ?? ($entry->deviations ?? []);
@endphp

@if(!empty($deviations))
    <div class="d-flex flex-wrap gap-1">
        @foreach($deviations as $deviation)
            @php
                $class = match($deviation['severity'] ?? 'info') {
                    'danger' => 'text-bg-danger',
                    'warning' => 'text-bg-warning',
                    default => 'text-bg-info',
                };
            @endphp

            <span class="badge rounded-pill {{ $class }}"
                  title="{{ $deviation['description'] ?? $deviation['label'] }}">
                <i class="bi bi-exclamation-triangle me-1"></i>{{ $deviation['label'] }}
            </span>
        @endforeach
    </div>
@endif
