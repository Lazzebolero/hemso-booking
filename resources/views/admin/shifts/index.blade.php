@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Schema</h2>
        <div class="page-subtitle">Hantera guidepass, arbetspass, möten och blockerade tider.</div>
    </div>

    <div class="page-actions">
        @if(Route::has('admin.shifts.calendar'))
            <a href="{{ route('admin.shifts.calendar') }}" class="btn btn-outline-secondary">
                <i class="bi bi-calendar3 me-2"></i>Kalender
            </a>
        @endif

        <a href="{{ route('admin.shifts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Ny schemapost
        </a>
    </div>
</div>

<div class="page-card compact-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="section-title mb-0">Lista</div>
        <div class="small-muted">{{ method_exists($shifts, 'total') ? $shifts->total() : count($shifts) }} poster</div>
    </div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th style="width: 120px;">Datum</th>
                    <th style="width: 150px;">Guide</th>
                    <th>Titel</th>
                    <th style="width: 130px;">Typ</th>
                    <th style="width: 120px;">Tid</th>
                    <th style="width: 120px;">Åtgärder</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shifts as $shift)
                    @php
                        $typeLabel = match($shift->shift_type) {
                            'tour' => 'Tur',
                            'work_shift' => 'Arbetspass',
                            'meeting' => 'Möte',
                            'maintenance' => 'Underhåll',
                            'blocked' => 'Blockerad',
                            default => ucfirst($shift->shift_type),
                        };

                        $typeClass = match($shift->shift_type) {
                            'tour' => 'badge-soft badge-soft-success',
                            'work_shift' => 'badge-soft badge-soft-secondary',
                            'meeting' => 'badge-soft badge-soft-warning',
                            'maintenance' => 'badge-soft badge-soft-danger',
                            'blocked' => 'badge-soft badge-soft-danger',
                            default => 'badge-soft badge-soft-secondary',
                        };
                    @endphp

                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $shift->shift_date?->format('Y-m-d') ?? '-' }}</div>
                        </td>

                        <td>
                            <div class="fw-semibold">{{ $shift->guide?->name ?? '-' }}</div>
                        </td>

                        <td>
                            <div class="fw-semibold">{{ $shift->title }}</div>
                            @if($shift->tour)
                                <div class="small-muted">{{ $shift->tour->title }}</div>
                            @endif
                        </td>

                        <td>
                            <span class="{{ $typeClass }}">{{ $typeLabel }}</span>
                        </td>

                        <td>
                            <div class="fw-semibold">
                                {{ $shift->start_time ? substr($shift->start_time, 0, 5) : '-' }}
                                –
                                {{ $shift->end_time ? substr($shift->end_time, 0, 5) : '-' }}
                            </div>
                        </td>

                        <td>
                            <a href="{{ route('admin.shifts.edit', $shift) }}" class="btn btn-sm btn-outline-secondary">
                                Redigera
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center muted py-4">Inga schemaposter hittades.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($shifts, 'links'))
        <div class="mt-3">
            {{ $shifts->links() }}
        </div>
    @endif
</div>
@endsection