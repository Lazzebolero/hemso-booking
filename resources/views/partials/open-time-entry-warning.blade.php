@php
    $openTimeEntryWarning = null;
    if (auth()->check() && class_exists(\App\Models\TimeEntry::class)) {
        $openTimeEntryWarning = \App\Models\TimeEntry::currentOpenForUser(auth()->id());
    }
@endphp

@if($openTimeEntryWarning)
    <div class="alert alert-warning d-flex align-items-center justify-content-between gap-3 mb-3">
        <div>
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Du har ett öppet arbetspass sedan
            <strong>{{ optional($openTimeEntryWarning->clock_in_at_original)->format('Y-m-d H:i') }}</strong>.
        </div>
        <a href="{{ route('time.index') }}" class="btn btn-sm btn-warning">
            <i class="bi bi-clock-history me-1"></i> Gå till tidrapportering
        </a>
    </div>
@endif
