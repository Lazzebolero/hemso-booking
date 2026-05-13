@extends('layouts.app')

@section('content')

@php
    $prefix = session('active_role') === 'host' ? 'host' : 'admin';
@endphp

<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
    <div>
        <h1 class="h3 mb-1">
            <i class="bi bi-clipboard-pulse me-2"></i>Tidkontroll
        </h1>
        <div class="text-muted">Tyst kontrollpanel för perioden {{ $period['label'] }}</div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        @if(Route::has('admin.time.index'))
            <a href="{{ route('admin.time.index', request()->query()) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-clock-history me-1"></i>Tidrapportering
            </a>
        @endif

        @if(Route::has($prefix . '.dashboard'))
            <a href="{{ route($prefix . '.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-speedometer2 me-1"></i>Dashboard
            </a>
        @endif
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Periodstatus</div>
                @if($periodReadyForExport)
                    <div class="badge text-bg-success mb-2">Klar</div>
                    <div class="small text-muted">Inga öppna eller väntande tider.</div>
                @else
                    <div class="badge text-bg-warning mb-2">Behöver granskas</div>
                    <div class="small text-muted">Det finns tider som bör kontrolleras.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Godkänd tid</div>
                <div class="fs-4 fw-bold">{{ $approvedFormatted }}</div>
                <div class="small text-muted">Av totalt {{ $totalFormatted }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Öppna pass</div>
                <div class="fs-4 fw-bold {{ $openCount > 0 ? 'text-warning' : '' }}">{{ $openCount }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Ej godkända</div>
                <div class="fs-4 fw-bold {{ $unapprovedCount > 0 ? 'text-primary' : '' }}">{{ $unapprovedCount }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Avvikelser</div>
                <div class="fs-4 fw-bold {{ $deviationCount > 0 ? 'text-warning' : '' }}">{{ $deviationCount }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Period</label>
                    <select name="period" class="form-select">
                        <option value="current" {{ request('period', 'current') === 'current' ? 'selected' : '' }}>Aktuell 21–20</option>
                        <option value="previous" {{ request('period') === 'previous' ? 'selected' : '' }}>Föregående 21–20</option>
                        <option value="custom" {{ request('period') === 'custom' ? 'selected' : '' }}>Valfri</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Från</label>
                    <input type="date" name="from" class="form-control" value="{{ request('from', $period['start_date']) }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Till</label>
                    <input type="date" name="to" class="form-control" value="{{ request('to', $period['end_date']) }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Visa</label>
                    <select name="view" class="form-select">
                        <option value="problems" {{ $selectedView === 'problems' ? 'selected' : '' }}>Kräver kontroll</option>
                        <option value="open" {{ $selectedView === 'open' ? 'selected' : '' }}>Öppna pass</option>
                        <option value="unapproved" {{ $selectedView === 'unapproved' ? 'selected' : '' }}>Ej godkända</option>
                        <option value="deviations" {{ $selectedView === 'deviations' ? 'selected' : '' }}>Avvikelser</option>
                        <option value="submitted" {{ $selectedView === 'submitted' ? 'selected' : '' }}>Inskickade</option>
                        <option value="corrected" {{ $selectedView === 'corrected' ? 'selected' : '' }}>Korrigerade</option>
                        <option value="all" {{ $selectedView === 'all' ? 'selected' : '' }}>Alla</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary flex-fill">
                            <i class="bi bi-funnel me-1"></i>Filtrera
                        </button>
                        <a href="{{ route('admin.time.control-panel') }}" class="btn btn-outline-secondary">Rensa</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
            <div>
                <h2 class="h5 mb-1">Kontrollista</h2>
                <div class="text-muted">{{ $visibleEntries->count() }} poster visas</div>
            </div>
        </div>

        <div class="control-list">
            <div class="control-grid control-header d-none d-xl-grid">
                <div>Personal</div>
                <div>Datum</div>
                <div>Tid</div>
                <div>Status</div>
                <div>Avvikelser</div>
                <div class="text-end">Åtgärder</div>
            </div>

            @forelse($visibleEntries as $entry)
                <div class="control-grid control-row {{ count($entry->deviations ?? []) > 0 ? 'has-deviation' : '' }}">
                    <div>
                        <div class="mobile-label">Personal</div>
                        <div class="fw-semibold text-truncate">{{ $entry->user->name ?? 'Okänd' }}</div>
                        @if($entry->user?->role)
                            <div class="small text-muted">{{ $entry->user->role }}</div>
                        @endif
                    </div>

                    <div>
                        <div class="mobile-label">Datum</div>
                        <div class="fw-semibold text-nowrap">{{ optional($entry->work_date)->format('Y-m-d') ?? $entry->work_date }}</div>
                    </div>

                    <div>
                        <div class="mobile-label">Tid</div>
                        <div class="fw-semibold text-nowrap">
                            {{ optional($entry->start_at)->format('H:i') ?? '-' }} – {{ optional($entry->end_at)->format('H:i') ?? '-' }}
                        </div>
                        <div class="small text-muted">{{ $entry->worked_hours_formatted }}</div>
                    </div>

                    <div>
                        <div class="mobile-label">Status</div>
                        <span class="badge {{ $entry->status_badge_class }}">{{ $entry->status_label }}</span>
                    </div>

                    <div>
                        <div class="mobile-label">Avvikelser</div>
                        @include('admin.time.partials.deviation-badges', ['entry' => $entry])
                    </div>

                    <div>
                        <div class="mobile-label">Åtgärder</div>
                        <div class="d-flex gap-2 justify-content-xl-end flex-wrap">
                            <a href="{{ route('admin.time.show', $entry) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye me-1"></i>Visa
                            </a>

                            @if(in_array($entry->status, [\App\Models\TimeEntry::STATUS_SUBMITTED, \App\Models\TimeEntry::STATUS_CORRECTED], true))
                                <form method="POST" action="{{ route('admin.time.approve', $entry) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-success">
                                        <i class="bi bi-check2-circle me-1"></i>Godkänn
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <div class="mb-2"><i class="bi bi-check2-circle display-6"></i></div>
                    <div class="fw-semibold">Inget att kontrollera</div>
                    <div class="small">Vald vy innehåller inga poster.</div>
                </div>
            @endforelse
        </div>
    </div>
</div>

<style>
.control-list{display:flex;flex-direction:column;gap:.65rem}
.control-grid{display:grid;grid-template-columns:minmax(180px,1.4fr) minmax(110px,.75fr) minmax(120px,.85fr) minmax(120px,.8fr) minmax(230px,1.4fr) minmax(170px,1fr);column-gap:1rem;align-items:center}
.control-header{padding:0 .95rem .25rem;color:#6c757d;font-size:.82rem;font-weight:600}
.control-row{border:1px solid rgba(15,23,42,.12);border-radius:.9rem;padding:.85rem .95rem;background:rgba(255,255,255,.98)}
.control-row.has-deviation{border-left:4px solid #ffc107}
.control-row:hover{border-color:rgba(37,99,235,.35);box-shadow:0 .35rem 1rem rgba(15,23,42,.07)}
.mobile-label{display:none;color:#6c757d;font-size:.78rem;margin-bottom:.15rem}
@media (max-width:1199.98px){.control-grid{grid-template-columns:repeat(3,minmax(0,1fr));row-gap:.85rem}.mobile-label{display:block}}
@media (max-width:767.98px){.control-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>

@endsection
