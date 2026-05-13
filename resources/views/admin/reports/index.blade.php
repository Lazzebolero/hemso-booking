@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Felrapporter</h2>
        <div class="page-subtitle">Översikt över inkomna rapporter och deras aktuella status.</div>
    </div>
</div>

<div class="page-card">
    <div class="section-title">Alla rapporter</div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th style="width: 140px;">Datum</th>
                    <th>Rubrik</th>
                    <th style="width: 150px;">Kategori</th>
                    <th style="width: 140px;">Klassning</th>
                    <th style="width: 150px;">Status</th>
                    <th style="width: 180px;">Plats</th>
                    <th style="width: 170px;">Rapporterad av</th>
                    <th style="width: 170px;">Tilldelad</th>
                    <th style="width: 180px;">Åtgärder</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $report)
                    @php
                        $statusCode = $report->statusRelation?->code ?? null;

                        $statusClass = match ($statusCode) {
                            'open' => 'badge-soft badge-soft-warning',
                            'in_progress' => 'badge-soft badge-soft-warning',
                            'resolved' => 'badge-soft badge-soft-success',
                            'closed' => 'badge-soft badge-soft-secondary',
                            default => 'badge-soft badge-soft-secondary',
                        };
                    @endphp

                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $report->created_at?->format('Y-m-d') ?? '-' }}</div>
                            <div class="small-muted">{{ $report->created_at?->format('H:i') ?? '' }}</div>
                        </td>

                        <td>
                            <div class="fw-semibold">{{ $report->title ?? '-' }}</div>
                            @if(!empty($report->description))
                                <div class="small-muted">{{ \Illuminate\Support\Str::limit($report->description, 90) }}</div>
                            @endif
                        </td>

                        <td>{{ $report->category?->name ?? '-' }}</td>
                        <td>{{ $report->priority?->name ?? '-' }}</td>

                        <td>
                            <span class="{{ $statusClass }}">
                                {{ $report->statusRelation?->name ?? '-' }}
                            </span>
                        </td>

                        <td>{{ $report->location?->name ?? $report->location_text ?? '-' }}</td>
                        <td>{{ $report->reporter?->name ?? '-' }}</td>
                        <td>{{ $report->assignee?->name ?? '-' }}</td>

                        <td>
                            <div class="toolbar-inline">
                                <a href="{{ route('admin.reports.show', $report) }}" class="btn btn-sm btn-outline-secondary">
                                    Visa
                                </a>

                                <a href="{{ route('admin.reports.edit', $report) }}" class="btn btn-sm btn-primary">
                                    Redigera
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center muted py-4">
                            Inga felrapporter hittades.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($reports, 'links'))
        <div class="mt-3">
            {{ $reports->links() }}
        </div>
    @endif
</div>
@endsection