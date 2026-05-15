@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'guide' => false,
])

@if($guide)
    <div class="page-header">
        <div>
            <div class="section-title mb-1">
                @if($icon)
                    <i class="bi {{ $icon }} me-2"></i>
                @endif
                {{ $title }}
            </div>
            @if($subtitle)
                <div class="small-muted">{{ $subtitle }}</div>
            @endif
        </div>
        @if(isset($actions))
            <div class="toolbar-inline">{{ $actions }}</div>
        @endif
    </div>
@else
    <div class="page-header">
        <div>
            <h2 class="page-title">
                @if($icon)
                    <i class="bi {{ $icon }} me-2"></i>
                @endif
                {{ $title }}
            </h2>
            @if($subtitle)
                <div class="page-subtitle">{{ $subtitle }}</div>
            @endif
        </div>
        @if(isset($actions))
            <div class="page-actions">{{ $actions }}</div>
        @endif
    </div>
@endif
