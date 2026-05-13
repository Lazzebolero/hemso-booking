@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
    <div>
        <h1 class="h3 mb-1">
            <i class="bi bi-clock-history me-2"></i>Tidrapportering
        </h1>
        <div class="text-muted">
            Stämpla tid, kontrollera pass och skicka in när allt stämmer.
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        @if($openEntry)
            <form method="POST" action="{{ route('time.clock-out') }}" data-offline-queue>
                @csrf
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-box-arrow-right me-1"></i>Stämpla ut
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('time.clock-in') }}" data-offline-queue>
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Stämpla in
                </button>
            </form>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm mb-4">
        {{ session('success') }}
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning border-0 shadow-sm mb-4">
        {{ session('warning') }}
    </div>
@endif

@if($openEntries->isNotEmpty())
    <div class="alert alert-warning border-0 shadow-sm mb-4">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="fw-bold mb-1">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Du har öppet arbetspass
                </div>

                @foreach($openEntries as $open)
                    <div class="small">
                        Start:
                        <strong>{{ optional($open->clock_in_at_original)->format('Y-m-d H:i') }}</strong>

                        @if($open->clock_in_at_original && $open->clock_in_at_original->isBefore(now()->startOfDay()))
                            <span class="badge text-bg-danger ms-2">Från tidigare dag</span>
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
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Vald period</div>
                <div class="fw-semibold">
                    {{ $from->format('Y-m-d') }} – {{ $to->format('Y-m-d') }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Totalt arbetad tid</div>
                <div class="fs-4 fw-bold">
                    {{ $totalFormatted }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-2">Snabbfilter</div>

                <div class="d-flex gap-2 flex-wrap">
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
</div>

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

<style>
    .time-list {
        display: flex;
        flex-direction: column;
        gap: .65rem;
    }

    .time-grid {
        display: grid;
        grid-template-columns:
            minmax(130px, 1fr)
            minmax(80px, .7fr)
            minmax(80px, .7fr)
            minmax(80px, .7fr)
            minmax(110px, .9fr)
            minmax(120px, .9fr)
            minmax(170px, 1.1fr);
        column-gap: 1rem;
        align-items: center;
    }

    .time-header {
        padding: 0 .95rem .25rem .95rem;
        color: #6c757d;
        font-size: .82rem;
        font-weight: 600;
    }

    .time-row {
        border: 1px solid rgba(15, 23, 42, .12);
        border-radius: .9rem;
        padding: .85rem .95rem;
        background: rgba(255,255,255,.98);
    }

    .time-row:hover {
        border-color: rgba(37, 99, 235, .35);
        box-shadow: 0 .35rem 1rem rgba(15,23,42,.07);
    }

    .time-row .btn {
        white-space: nowrap;
    }

    .mobile-label {
        display: none;
        color: #6c757d;
        font-size: .78rem;
        margin-bottom: .15rem;
    }

    @media (max-width: 1199.98px) {
        .time-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            row-gap: .85rem;
        }

        .mobile-label {
            display: block;
        }
    }

    @media (max-width: 767.98px) {
        .time-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>

@endsection
