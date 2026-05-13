@extends('layouts.guide')

@section('content')
<div class="page-header">
    <div>
        <div class="section-title mb-1">
            <i class="bi bi-clock-history me-2"></i>Tidrapportering
        </div>
        <div class="small-muted">
            Stämpla tid, kontrollera pass och skicka in när allt stämmer.
        </div>
    </div>

    <div class="toolbar-inline">
        @if($openEntry)
            <form method="POST" action="{{ route('time.clock-out') }}" data-offline-queue>
                @csrf
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-box-arrow-right me-2"></i>Stämpla ut
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('time.clock-in') }}" data-offline-queue>
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Stämpla in
                </button>
            </form>
        @endif
    </div>
</div>

@if(session('warning'))
    <div class="system-message-banner system-message-important mb-3">
        <div class="system-message-title">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('warning') }}
        </div>
    </div>
@endif

@if($openEntries->isNotEmpty())
    <div class="system-message-banner system-message-important mb-4">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="system-message-title">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Du har öppet arbetspass
                </div>

                @foreach($openEntries as $open)
                    <div class="system-message-body">
                        Start:
                        <strong>{{ optional($open->clock_in_at_original)->format('Y-m-d H:i') }}</strong>

                        @if(optional($open->clock_in_at_original)->isBefore(now()->startOfDay()))
                            <span class="badge-soft badge-soft-danger ms-2">Från tidigare dag</span>
                        @endif
                    </div>
                @endforeach
            </div>

            @if($openEntry)
                <form method="POST" action="{{ route('time.clock-out') }}" data-offline-queue>
                    @csrf
                    <button type="submit" class="btn btn-sm btn-danger">
                        Stämpla ut nu
                    </button>
                </form>
            @endif
        </div>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="page-card h-100">
            <div class="small-muted mb-1">Vald period</div>
            <div class="fw-semibold">
                {{ $from->format('Y-m-d') }} – {{ $to->format('Y-m-d') }}
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="page-card h-100">
            <div class="small-muted mb-1">Totalt arbetad tid</div>
            <div class="fs-4 fw-bold">
                {{ $totalFormatted }}
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="page-card h-100">
            <div class="small-muted mb-2">Snabbfilter</div>
            <div class="toolbar-inline">
                <a href="{{ route('time.index', ['filter' => 'today']) }}"
                   class="btn btn-sm {{ $filter === 'today' ? 'btn-primary' : 'btn-outline-secondary' }}">Idag</a>

                <a href="{{ route('time.index', ['filter' => 'week']) }}"
                   class="btn btn-sm {{ $filter === 'week' ? 'btn-primary' : 'btn-outline-secondary' }}">Vecka</a>

                <a href="{{ route('time.index', ['filter' => 'month']) }}"
                   class="btn btn-sm {{ $filter === 'month' ? 'btn-primary' : 'btn-outline-secondary' }}">Månad</a>

                <a href="{{ route('time.index', ['filter' => '30days']) }}"
                   class="btn btn-sm {{ $filter === '30days' ? 'btn-primary' : 'btn-outline-secondary' }}">30 dagar</a>
            </div>
        </div>
    </div>
</div>

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

<style>
    .guide-time-row {
        border: 1px solid rgba(15, 23, 42, .12);
        border-radius: 1rem;
        padding: 1rem;
        margin-bottom: .85rem;
        background: rgba(255, 255, 255, .92);
    }

    .guide-time-row:last-child {
        margin-bottom: 0;
    }

    .guide-time-row:hover {
        border-color: rgba(37, 99, 235, .35);
        box-shadow: 0 .5rem 1.25rem rgba(15, 23, 42, .08);
    }

    .guide-time-row .btn {
        white-space: nowrap;
    }
</style>
@endsection
