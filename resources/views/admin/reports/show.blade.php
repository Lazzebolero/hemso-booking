@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">{{ $report->title }}</h2>
        <div class="muted">Visning av felrapport</div>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.reports.edit', $report) }}" class="btn btn-primary">
            <i class="bi bi-pencil-square me-2"></i>Redigera
        </a>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<div class="page-card">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="section-title">Beskrivning</div>
            <p class="mb-0">{{ $report->description }}</p>
        </div>

        <div class="col-lg-4">
            <div class="page-card h-100">
                <div class="section-title">Information</div>

                <div class="mb-3">
                    <div class="small muted">Kategori</div>
                    <div class="fw-semibold">{{ $report->category }}</div>
                </div>

                <div class="mb-3">
                    <div class="small muted">Prioritet</div>
                    <div class="fw-semibold">{{ $report->priority }}</div>
                </div>

                <div class="mb-3">
                    <div class="small muted">Status</div>
                    <div class="fw-semibold">{{ $report->status }}</div>
                </div>

                <div class="mb-3">
                    <div class="small muted">Plats</div>
                    <div class="fw-semibold">{{ $report->location ?: '-' }}</div>
                </div>

                <div class="mb-3">
                    <div class="small muted">Skapad</div>
                    <div class="fw-semibold">{{ $report->created_at }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection