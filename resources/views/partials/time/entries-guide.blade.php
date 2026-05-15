@props(['entries'])

<div class="page-card">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
        <div>
            <div class="section-title mb-1">Mina tider</div>
            <div class="small-muted">{{ $entries->count() }} pass i vald period</div>
        </div>
    </div>

    @forelse($entries as $entry)
        <div class="guide-time-row">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-lg-2">
                    <div class="small-muted mb-1">Datum</div>
                    <div class="fw-semibold">{{ optional($entry->work_date)->format('Y-m-d') }}</div>
                    <div class="small-muted">{{ optional($entry->work_date)->translatedFormat('l') }}</div>
                </div>

                <div class="col-6 col-lg-2">
                    <div class="small-muted mb-1">Original</div>
                    <div class="fw-semibold text-nowrap">
                        {{ optional($entry->clock_in_at_original)->format('H:i') ?? '-' }}
                        –
                        {{ optional($entry->clock_out_at_original)->format('H:i') ?? '-' }}
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <div class="small-muted mb-1">Rapporterad</div>
                    <div class="fw-semibold text-nowrap">
                        {{ optional($entry->start_at)->format('H:i') ?? '-' }}
                        –
                        {{ optional($entry->end_at)->format('H:i') ?? '-' }}
                    </div>
                </div>

                <div class="col-6 col-lg-1">
                    <div class="small-muted mb-1">Rast</div>
                    <div class="fw-semibold">{{ (int) $entry->break_minutes }} min</div>
                </div>

                <div class="col-6 col-lg-1">
                    <div class="small-muted mb-1">Tid</div>
                    <div class="fw-semibold">{{ $entry->worked_hours_formatted }}</div>
                </div>

                <div class="col-6 col-lg-2">
                    <div class="small-muted mb-1">Status</div>
                    <span class="{{ $entry->status === \App\Models\TimeEntry::STATUS_OPEN ? 'badge-soft badge-soft-warning' : ($entry->status === \App\Models\TimeEntry::STATUS_SUBMITTED ? 'badge-soft badge-soft-success' : 'badge-soft badge-soft-secondary') }}">
                        {{ $entry->status_label }}
                    </span>

                    @if($entry->audits_count > 0)
                        <span class="badge-soft badge-soft-secondary ms-1">
                            {{ $entry->audits_count }} ändr.
                        </span>
                    @endif
                </div>

                <div class="col-12 col-lg-2">
                    <div class="toolbar-inline justify-content-lg-end">
                        @if($entry->isEditableByUser())
                            <a href="{{ route('time.edit', $entry) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil-square me-1"></i>Redigera
                            </a>
                        @endif

                        @if($entry->status === \App\Models\TimeEntry::STATUS_DRAFT)
                            <form method="POST" action="{{ route('time.submit', $entry) }}" data-offline-queue>
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="bi bi-send-check me-1"></i>Skicka in
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-5">
            <div class="small-muted mb-2">
                <i class="bi bi-clock-history display-6"></i>
            </div>
            <div class="fw-semibold">Inga tider för vald period</div>
            <div class="small-muted">Byt filter eller stämpla in ett nytt pass.</div>
        </div>
    @endforelse
</div>
