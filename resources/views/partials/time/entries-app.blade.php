@props(['entries'])

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
        <div>
            <div class="fw-semibold">
                <i class="bi bi-list-ul me-2"></i>Mina tider
            </div>

            <div class="small text-muted">
                {{ $entries->count() }} poster
            </div>
        </div>
    </div>

    <div class="card-body">

        <div class="time-list">

            <div class="time-grid time-header d-none d-xl-grid">
                <div>Datum</div>
                <div>Start</div>
                <div>Slut</div>
                <div>Rast</div>
                <div>Arbetstid</div>
                <div>Status</div>
                <div class="text-end">Åtgärder</div>
            </div>

            @forelse($entries as $entry)

                <div class="time-grid time-row">

                    <div>
                        <div class="mobile-label">Datum</div>
                        <div class="fw-semibold text-nowrap">
                            {{ optional($entry->work_date)->format('Y-m-d') ?? $entry->work_date }}
                        </div>
                    </div>

                    <div>
                        <div class="mobile-label">Start</div>
                        <div class="fw-semibold text-nowrap">
                            {{ optional($entry->start_at)->format('H:i') ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="mobile-label">Slut</div>
                        <div class="fw-semibold text-nowrap">
                            {{ optional($entry->end_at)->format('H:i') ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="mobile-label">Rast</div>
                        <div class="fw-semibold text-nowrap">
                            {{ (int) $entry->break_minutes }} min
                        </div>
                    </div>

                    <div>
                        <div class="mobile-label">Arbetstid</div>
                        <div class="fw-semibold text-nowrap">
                            {{ $entry->worked_hours_formatted }}
                        </div>
                    </div>

                    <div>
                        <div class="mobile-label">Status</div>
                        <span class="badge {{ $entry->status_badge_class }}">
                            {{ $entry->status_label }}
                        </span>
                    </div>

                    <div>
                        <div class="mobile-label">Åtgärder</div>

                        <div class="d-flex gap-2 justify-content-xl-end flex-wrap">
                            @if($entry->isEditableByUser())
                                <a href="{{ route('time.edit', $entry) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil me-1"></i>Redigera
                                </a>
                            @endif

                            @if($entry->status === \App\Models\TimeEntry::STATUS_DRAFT)
                                <form method="POST" action="{{ route('time.submit', $entry) }}" data-offline-queue>
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-send me-1"></i>Skicka in
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                </div>

            @empty

                <div class="text-center py-5 text-muted">
                    Inga tider hittades.
                </div>

            @endforelse

        </div>

    </div>
</div>

@if(method_exists($entries, 'links'))
    <div class="mt-4">
        {{ $entries->links() }}
    </div>
@endif
