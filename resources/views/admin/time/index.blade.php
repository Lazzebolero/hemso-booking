@extends('layouts.app')

@section('content')

@php
    $prefix = session('active_role') === 'host' ? 'host' : 'admin';
@endphp

<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
    <div>
        <h1 class="h3 mb-1">
            <i class="bi bi-clock-history me-2"></i>Tidrapportering
        </h1>

        <div class="text-muted">
            Period: {{ $period['label'] }}
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        @if(Route::has($prefix . '.dashboard'))
            <a href="{{ route($prefix . '.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard
            </a>
        @endif

        @if(Route::has('admin.time.payroll-locks.index'))
            <a href="{{ route('admin.time.payroll-locks.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-lock me-2"></i>Lönelås
            </a>
        @endif

    @if(Route::has('admin.time.export'))
        <a href="{{ route('admin.time.export', request()->query()) }}"
           class="btn btn-sm btn-success">
            <i class="bi bi-file-earmark-excel me-2"></i>Excel (enligt filter)
        </a>
    @endif

    @if(Route::has('admin.time.export.entries-csv'))
    <a href="{{ route('admin.time.export.entries-csv', request()->query()) }}"
       class="btn btn-sm btn-success">
        <i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV pass
    </a>
@endif

@if(Route::has('admin.time.export.summary-csv'))
    <a href="{{ route('admin.time.export.summary-csv', request()->query()) }}"
       class="btn btn-sm btn-success text-white">
        <i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV summering
    </a>
@endif
        @if(Route::has('admin.time.payroll-pdf.all'))
            <a href="{{ route('admin.time.payroll-pdf.all', request()->query()) }}"
               class="btn btn-sm btn-outline-danger">
                <i class="bi bi-filetype-pdf me-2"></i>Löneunderlag PDF
            </a>
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

@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm mb-4">
        <div class="fw-semibold mb-1">Kunde inte tillämpa filtret</div>
        <ul class="mb-0 small ps-3">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Vald period</div>
                <div class="fw-semibold">{{ $period['label'] }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Totalt arbetad tid</div>
                <div class="fs-4 fw-bold">{{ $totalFormatted }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Antal personer</div>
                <div class="fs-4 fw-bold">{{ $summary->count() }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 {{ $totalDeviations > 0 ? 'border-warning' : '' }}">
            <div class="card-body">
                <div class="text-muted small mb-1">Avvikelser</div>
                <div class="fs-4 fw-bold {{ $totalDeviations > 0 ? 'text-warning' : '' }}">
                    {{ $totalDeviations }}
                </div>
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
                        <option value="current" {{ old('period', request('period', 'current')) === 'current' ? 'selected' : '' }}>Aktuell 21–20</option>
                        <option value="previous" {{ old('period', request('period', 'current')) === 'previous' ? 'selected' : '' }}>Föregående 21–20</option>
                        <option value="custom" {{ old('period', request('period', 'current')) === 'custom' ? 'selected' : '' }}>Valfri</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Från</label>
                    <input type="date" name="from" class="form-control @error('from') is-invalid @enderror" value="{{ old('from', request('from', $period['start_date'])) }}">
                    @error('from')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label">Till</label>
                    <input type="date" name="to" class="form-control @error('to') is-invalid @enderror" value="{{ old('to', request('to', $period['end_date'])) }}">
                    @error('to')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label">Användare</label>
                    <select name="user_id" class="form-select">
                        <option value="">Alla</option>

                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Alla</option>
                        <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Öppet</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Utkast</option>
                        <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Inskickad</option>
                        <option value="corrected" {{ request('status') === 'corrected' ? 'selected' : '' }}>Korrigerad</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Godkänd</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary flex-fill">
                            <i class="bi bi-funnel me-1"></i>Filtrera
                        </button>

                        <a href="{{ route('admin.time.index') }}" class="btn btn-outline-secondary">
                            Rensa
                        </a>
                    </div>
                </div>

            </div>
        </form>

    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">

        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
            <div>
                <h2 class="h5 mb-1">
                    <i class="bi bi-people me-2"></i>Summering per person
                </h2>

                <div class="text-muted">
                    Periodens totalsummor
                </div>
            </div>

            <div class="text-end">
                <div class="small text-muted">Total arbetstid</div>

                <div class="fw-bold fs-5">
                    {{ $totalFormatted }}
                </div>
            </div>
        </div>

        <div class="summary-list">

            @forelse($summary as $row)

                <div class="summary-row">

                    <div class="summary-main">
                        <div class="fw-semibold">
                            {{ $row['user']->name ?? 'Okänd' }}
                        </div>

                        @if($row['user']?->role)
                            <div class="text-muted small">
                                {{ $row['user']->role }}
                            </div>
                        @endif
                    </div>

                    <div class="summary-stat">
                        <div class="summary-label">Pass</div>
                        <div class="summary-value">{{ $row['passes'] }}</div>
                    </div>

                    <div class="summary-stat">
                        <div class="summary-label">Total tid</div>
                        <div class="summary-value">{{ $row['formatted'] }}</div>
                    </div>

                    <div class="summary-stat">
                        <div class="summary-label">Avvikelser</div>
                        <span class="badge rounded-pill {{ $row['deviations'] > 0 ? 'text-bg-warning' : 'text-bg-light border' }}">
                            {{ $row['deviations'] }}
                        </span>
                    </div>

                    <div class="summary-stat">
                        <div class="summary-label">Öppna</div>
                        <span class="badge rounded-pill text-bg-warning">{{ $row['open'] }}</span>
                    </div>

                    <div class="summary-stat">
                        <div class="summary-label">Inskickade</div>
                        <span class="badge rounded-pill text-bg-primary">{{ $row['submitted'] }}</span>
                    </div>

                    <div class="summary-stat">
                        <div class="summary-label">Korrigerade</div>
                        <span class="badge rounded-pill text-bg-info">{{ $row['corrected'] }}</span>
                    </div>

                    <div class="summary-stat">
                        <div class="summary-label">Godkända</div>
                        <span class="badge rounded-pill text-bg-success">{{ $row['approved'] }}</span>
                    </div>

                    @if(Route::has('admin.time.payroll-pdf.person') && $row['approved'] > 0)
                        <div class="summary-stat align-self-center">
                            <a href="{{ route('admin.time.payroll-pdf.person', array_merge(request()->query(), ['user' => $row['user']->id])) }}"
                               class="btn btn-sm btn-outline-danger text-nowrap"
                               title="Ladda ner löneunderlag PDF (endast godkända pass)">
                                <i class="bi bi-filetype-pdf me-1"></i>PDF
                            </a>
                        </div>
                    @endif

                </div>

            @empty

                <div class="text-center py-5 text-muted">
                    Ingen summering för vald period.
                </div>

            @endforelse

        </div>

    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">

        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
            <div>
                <h2 class="h5 mb-1">Personalens tider</h2>
                <div class="text-muted">
                    {{ method_exists($entries, 'total') ? $entries->total() : $entries->count() }} poster hittades
                </div>
            </div>
        </div>

        <div class="admin-time-list">

            <div class="admin-time-grid admin-time-header d-none d-xl-grid">
                <div class="admin-time-cell">Personal</div>
                <div class="admin-time-cell">Datum</div>
                <div class="admin-time-cell">Original</div>
                <div class="admin-time-cell">Rapporterad</div>
                <div class="admin-time-cell">Rast</div>
                <div class="admin-time-cell">Tid</div>
                <div class="admin-time-cell">Status / Avvikelser</div>
                <div class="admin-time-cell text-end">Åtgärder</div>
            </div>

            @forelse($entries as $entry)

                <div class="admin-time-grid admin-time-row {{ count($entry->deviations ?? []) > 0 ? 'has-deviation' : '' }}">

                    <div class="admin-time-cell admin-time-person">
                        <div class="mobile-label">Personal</div>
                        <div class="fw-semibold text-truncate">
                            {{ $entry->user->name ?? 'Okänd' }}
                        </div>

                        @if($entry->user?->role)
                            <div class="text-muted small text-truncate">
                                {{ $entry->user->role }}
                            </div>
                        @endif
                    </div>

                    <div class="admin-time-cell">
                        <div class="mobile-label">Datum</div>
                        <div class="fw-semibold text-nowrap">
                            {{ optional($entry->work_date)->format('Y-m-d') ?? $entry->work_date }}
                        </div>
                    </div>

                    <div class="admin-time-cell">
                        <div class="mobile-label">Original</div>
                        <div class="fw-semibold text-nowrap">
                            {{ optional($entry->clock_in_at_original)->format('H:i') ?? '-' }}
                            –
                            {{ optional($entry->clock_out_at_original)->format('H:i') ?? '-' }}
                        </div>
                    </div>

                    <div class="admin-time-cell">
                        <div class="mobile-label">Rapporterad</div>
                        <div class="fw-semibold text-nowrap">
                            {{ optional($entry->start_at)->format('H:i') ?? '-' }}
                            –
                            {{ optional($entry->end_at)->format('H:i') ?? '-' }}
                        </div>
                    </div>

                    <div class="admin-time-cell">
                        <div class="mobile-label">Rast</div>
                        <div class="fw-semibold text-nowrap">
                            {{ (int) $entry->break_minutes }} min
                        </div>
                    </div>

                    <div class="admin-time-cell">
                        <div class="mobile-label">Tid</div>
                        <div class="fw-semibold text-nowrap">
                            {{ $entry->worked_hours_formatted }}
                        </div>
                    </div>

                    <div class="admin-time-cell">
                        <div class="mobile-label">Status / Avvikelser</div>
                        <div class="d-flex flex-column gap-1">
                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                <span class="badge {{ $entry->status_badge_class }}">
                                    {{ $entry->status_label }}
                                </span>

                                @if(($entry->audits_count ?? 0) > 0)
                                    <span class="badge text-bg-light border" title="Antal ändringar">
                                        {{ $entry->audits_count }}
                                    </span>
                                @endif
                            </div>

                            @include('admin.time.partials.deviation-badges', ['entry' => $entry])
                        </div>
                    </div>

                    <div class="admin-time-cell admin-time-actions">
                        <div class="d-flex gap-2 justify-content-xl-end flex-nowrap">

                            <a href="{{ route('admin.time.show', $entry) }}"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye me-1"></i>Visa
                            </a>

                            @if(in_array($entry->status, [\App\Models\TimeEntry::STATUS_SUBMITTED, \App\Models\TimeEntry::STATUS_CORRECTED], true))
                                <form method="POST" action="{{ route('admin.time.approve', $entry) }}">
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-check2-circle me-1"></i>Godkänn
                                    </button>
                                </form>
                            @endif

                        </div>
                    </div>

                </div>

            @empty
                <div class="text-center py-5 text-muted">
                    <div class="mb-2">
                        <i class="bi bi-clock-history display-6"></i>
                    </div>

                    <div class="fw-semibold">Inga tider hittades</div>
                    <div class="small">Ändra filter eller datumintervall.</div>
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
    .summary-list {
        display: flex;
        flex-direction: column;
        gap: .75rem;
    }

    .summary-row {
        display: grid;
        grid-template-columns:
            minmax(220px, 1.6fr)
            repeat(7, minmax(90px, .7fr));
        gap: 1rem;
        align-items: center;
        border: 1px solid rgba(15, 23, 42, .12);
        border-radius: 1rem;
        padding: 1rem 1.1rem;
        background: rgba(255,255,255,.96);
    }

    .summary-row:hover {
        border-color: rgba(37, 99, 235, .35);
        box-shadow: 0 .35rem 1rem rgba(15,23,42,.07);
    }

    .summary-label {
        font-size: .78rem;
        color: #6c757d;
        margin-bottom: .2rem;
    }

    .summary-value {
        font-weight: 600;
    }

    .admin-time-list {
        display: flex;
        flex-direction: column;
        gap: .65rem;
    }

    .admin-time-grid {
        display: grid;
        grid-template-columns:
            minmax(170px, 1.35fr)
            minmax(105px, .7fr)
            minmax(105px, .7fr)
            minmax(120px, .8fr)
            minmax(70px, .45fr)
            minmax(80px, .5fr)
            minmax(190px, 1.15fr)
            minmax(170px, .9fr);
        column-gap: 1rem;
        align-items: center;
    }

    .admin-time-header {
        padding: 0 .95rem .25rem .95rem;
        color: #6c757d;
        font-size: .82rem;
        font-weight: 600;
    }

    .admin-time-row {
        border: 1px solid rgba(15, 23, 42, .12);
        border-radius: .9rem;
        padding: .85rem .95rem;
        background: rgba(255, 255, 255, .98);
    }

    .admin-time-row.has-deviation {
        border-left: 4px solid #ffc107;
    }

    .admin-time-row:hover {
        border-color: rgba(37, 99, 235, .35);
        box-shadow: 0 .35rem 1rem rgba(15, 23, 42, .07);
    }

    .admin-time-cell {
        min-width: 0;
    }

    .admin-time-actions .btn {
        white-space: nowrap;
    }

    .mobile-label {
        display: none;
        color: #6c757d;
        font-size: .78rem;
        margin-bottom: .15rem;
    }

    @media (max-width: 1199.98px) {
        .summary-row {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .summary-main {
            grid-column: span 4;
        }

        .admin-time-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            row-gap: .85rem;
        }

        .admin-time-actions {
            grid-column: span 4;
        }

        .mobile-label {
            display: block;
        }
    }

    @media (max-width: 767.98px) {
        .summary-row {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .summary-main {
            grid-column: span 2;
        }

        .admin-time-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .admin-time-person,
        .admin-time-actions {
            grid-column: span 2;
        }

        .admin-time-actions .d-flex {
            justify-content: flex-start !important;
            flex-wrap: wrap !important;
        }
    }
</style>

@endsection
