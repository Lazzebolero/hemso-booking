<div class="page-header">
    
	<div class="page-header">
    <div>
        <h2 class="page-title">{{ $title }}</h2>
        @isset($subtitle)
            <div class="page-subtitle">{{ $subtitle }}</div>
        @endisset
    </div>

    @if (isset($actions) && trim((string) $actions) !== '')
        <div class="page-actions">
            {{ $actions }}
        </div>
    @endif
</div>

    @if (trim($actions ?? '') !== '')
        <div class="page-actions">
            {{ $actions }}
        </div>
    @endif
</div>