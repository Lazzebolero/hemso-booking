@props(['guide' => false])

@if(session('success'))
    @if($guide)
        <div class="system-message-banner system-message-success mb-3">
            <div class="system-message-title">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            </div>
        </div>
    @else
        <div class="alert alert-success border-0 shadow-sm mb-4">
            {{ session('success') }}
        </div>
    @endif
@endif

@if(session('warning'))
    @if($guide)
        <div class="system-message-banner system-message-important mb-3">
            <div class="system-message-title">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('warning') }}
            </div>
        </div>
    @else
        <div class="alert alert-warning border-0 shadow-sm mb-4">
            {{ session('warning') }}
        </div>
    @endif
@endif
