@extends('layouts.app')

@section('content')
@php
    $statusCode = $report->statusRelation?->code ?? null;

    $statusClass = match ($statusCode) {
        'open' => 'badge-soft badge-soft-warning',
        'in_progress' => 'badge-soft badge-soft-warning',
        'resolved' => 'badge-soft badge-soft-success',
        'closed' => 'badge-soft badge-soft-secondary',
        default => 'badge-soft badge-soft-secondary',
    };

    $reportsRoutePrefix = request()->routeIs('host.*') ? 'host' : 'admin';
    $reportAttachmentUrl = filled($report->attachment_path ?? null)
        ? route($reportsRoutePrefix.'.reports.attachment', $report)
        : null;
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Felrapport</h2>
        <div class="page-subtitle">Detaljer och uppföljning för rapporten.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route($reportsRoutePrefix.'.reports.edit', $report) }}" class="btn btn-primary">
            <i class="bi bi-pencil-square me-2"></i>Redigera
        </a>

        <a href="{{ route($reportsRoutePrefix.'.reports.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<div class="admin-grid-2">
    <div>
        <div class="page-card mb-4">
            <div class="section-title">Översikt</div>

            <div class="row g-3">
                <div class="col-md-8">
                    <div class="info-item">
                        <div class="small-muted mb-1">Rubrik</div>
                        <div class="fw-semibold">{{ $report->title ?? '-' }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="info-item">
                        <div class="small-muted mb-1">Status</div>
                        <div>
                            <span class="{{ $statusClass }}">
                                {{ $report->statusRelation?->name ?? '-' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="info-item">
                        <div class="small-muted mb-1">Kategori</div>
                        <div class="fw-semibold">{{ $report->category?->name ?? '-' }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="info-item">
                        <div class="small-muted mb-1">Klassning</div>
                        <div class="fw-semibold">{{ $report->priority?->name ?? '-' }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="info-item">
                        <div class="small-muted mb-1">Plats</div>
                        <div class="fw-semibold">
                            {{ $report->location?->name ?? $report->location_text ?? '-' }}
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-item">
                        <div class="small-muted mb-1">Rapporterad av</div>
                        <div class="fw-semibold">{{ $report->reporter?->name ?? '-' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-item">
                        <div class="small-muted mb-1">Tilldelad till</div>
                        <div class="fw-semibold">{{ $report->assignee?->name ?? '-' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-item">
                        <div class="small-muted mb-1">Skapad</div>
                        <div class="fw-semibold">{{ $report->created_at?->format('Y-m-d H:i') ?? '-' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-item">
                        <div class="small-muted mb-1">Senast uppdaterad</div>
                        <div class="fw-semibold">{{ $report->updated_at?->format('Y-m-d H:i') ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-card">
            <div class="section-title">Bifogad bild</div>

            @if($reportAttachmentUrl)
                <div class="info-item mb-3">
                    <a href="{{ $reportAttachmentUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm mb-2">
                        <i class="bi bi-box-arrow-up-right me-2"></i>Öppna bild i full storlek
                    </a>
                    <div class="rounded overflow-hidden border" style="max-width: 640px;">
                        <img
                            src="{{ $reportAttachmentUrl }}"
                            alt="Bifogad bild till felrapporten"
                            class="img-fluid d-block"
                            loading="lazy"
                        >
                    </div>
                </div>
            @else
                <div class="info-item">
                    <span class="muted">Ingen bild bifogad.</span>
                </div>
            @endif
        </div>

        <div class="page-card">
            <div class="section-title">Beskrivning</div>

            <div class="info-item">
                @if(!empty($report->description))
                    {!! nl2br(e($report->description)) !!}
                @else
                    <span class="muted">Ingen beskrivning angiven.</span>
                @endif
            </div>
        </div>
    </div>

    <div>
        <div class="page-card compact-card mb-4">
            <div class="section-title">Snabbåtgärder</div>

            <div class="toolbar-inline">
                <a href="{{ route($reportsRoutePrefix.'.reports.edit', $report) }}" class="btn btn-primary w-100">
                    <i class="bi bi-pencil-square me-2"></i>Redigera rapport
                </a>
            </div>
        </div>

        <div class="page-card compact-card">
            <div class="section-title">Sammanfattning</div>

            <div class="info-item mb-3">
                <div class="small-muted mb-1">Status</div>
                <span class="{{ $statusClass }}">
                    {{ $report->statusRelation?->name ?? '-' }}
                </span>
            </div>

            <div class="info-item mb-3">
                <div class="small-muted mb-1">Kategori</div>
                <div class="fw-semibold">{{ $report->category?->name ?? '-' }}</div>
            </div>

            <div class="info-item mb-3">
                <div class="small-muted mb-1">Klassning</div>
                <div class="fw-semibold">{{ $report->priority?->name ?? '-' }}</div>
            </div>

            <div class="info-item">
                <div class="small-muted mb-1">Plats</div>
                <div class="fw-semibold">
                    {{ $report->location?->name ?? $report->location_text ?? '-' }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection