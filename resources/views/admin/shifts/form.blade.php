@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">{{ $shift->exists ? 'Redigera schemapost' : 'Ny schemapost' }}</h2>
        <div class="page-subtitle">
            {{ $shift->exists ? 'Uppdatera guide, tid och typ för passet.' : 'Skapa en ny schemapost för guide eller verksamhet.' }}
        </div>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.shifts.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<form method="POST" action="{{ $shift->exists ? route('admin.shifts.update', $shift) : route('admin.shifts.store') }}">
    @csrf
    @if($shift->exists)
        @method('PUT')
    @endif

    <div class="page-card">
        <div class="shift-form-grid">
            <div class="shift-form-main">
                <div class="section-title">Schemainformation</div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Guide</label>
                        <select name="guide_id" class="form-select">
                            @foreach($guides as $guide)
                                <option value="{{ $guide->id }}" @selected(old('guide_id', $shift->guide_id) == $guide->id)>
                                    {{ $guide->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tur (valfri)</label>
                        <select name="tour_id" class="form-select">
                            <option value="">Ingen</option>
                            @foreach($tours as $tour)
                                <option value="{{ $tour->id }}" @selected(old('tour_id', $shift->tour_id) == $tour->id)>
                                    {{ $tour->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Typ</label>
                        <select name="shift_type" class="form-select">
                            @foreach([
                                'tour' => 'Tur',
                                'work_shift' => 'Arbetspass',
                                'meeting' => 'Möte',
                                'maintenance' => 'Underhåll',
                                'blocked' => 'Blockerad'
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('shift_type', $shift->shift_type ?? 'tour') == $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Titel</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $shift->title) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Datum</label>
                        <input
                            type="date"
                            name="shift_date"
                            class="form-control"
                            value="{{ old('shift_date', optional($shift->shift_date)->format('Y-m-d')) }}"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Start</label>
                        <input type="time" name="start_time" class="form-control" value="{{ old('start_time', $shift->start_time) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Slut</label>
                        <input type="time" name="end_time" class="form-control" value="{{ old('end_time', $shift->end_time) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notering</label>
                        <textarea name="notes" class="form-control" rows="5">{{ old('notes', $shift->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="shift-form-side">
                <div class="form-side-box">
                    <div class="info-label">Spara</div>
                    <div class="small-muted mb-3">
                        Kontrollera guide, datum och tider innan du sparar schemaposten.
                    </div>

                    <button class="btn btn-primary w-100">
                        <i class="bi bi-save me-2"></i>Spara schemapost
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.shift-form-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 280px;
    gap: 1rem;
    align-items: start;
}
.form-side-box {
    background: #f8fafc;
    border: 1px solid var(--brand-line-soft);
    border-radius: 12px;
    padding: 0.95rem;
}
@media (max-width: 1100px) {
    .shift-form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection