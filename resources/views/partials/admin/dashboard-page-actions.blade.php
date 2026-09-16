@props([
    'prefix',
])

<div class="page-actions dashboard-page-actions">
    @if(Route::has($prefix . '.bookings.quick-create'))
        <a href="{{ route($prefix . '.bookings.quick-create') }}" class="btn btn-primary">
            <i class="bi bi-list-ol me-2"></i>Bokningssekvens
        </a>
    @endif

    @if(Route::has($prefix . '.group-bookings.create'))
        <a href="{{ route($prefix . '.group-bookings.create') }}" class="btn btn-outline-secondary">
            <i class="bi bi-calendar-plus me-2"></i>Gruppbokning
        </a>
    @endif

    @if(Route::has('quick-tours.create'))
        <a href="{{ route('quick-tours.create') }}" class="btn btn-outline-secondary">
            <i class="bi bi-lightning-charge-fill me-2"></i>Snabbtur
        </a>
    @endif

    @if(Route::has($prefix . '.tours.create'))
        <a href="{{ route($prefix . '.tours.create') }}" class="btn btn-outline-secondary">
            <i class="bi bi-plus-circle me-2"></i>Ny tur
        </a>
    @endif

    @if(Route::has($prefix . '.ferry-timetable.index'))
        <a href="{{ route($prefix . '.ferry-timetable.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-table me-2"></i>Färjetidtabell
        </a>
    @endif

    @if($prefix === 'host' && Route::has($prefix . '.memories.create'))
        <a href="{{ route($prefix . '.memories.create') }}" class="btn btn-outline-secondary">
            <i class="bi bi-journal-text me-2"></i>Spara minne
        </a>
    @endif
</div>
