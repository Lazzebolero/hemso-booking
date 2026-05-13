@extends('layouts.app')
@section('content')
<h1>{{ $tour->exists ? 'Redigera tur' : 'Ny tur' }}</h1>
<form method="POST" action="{{ $tour->exists ? route('admin.tours.update',$tour) : route('admin.tours.store') }}">
@csrf
@if($tour->exists) @method('PUT') @endif
<div class="grid grid-3">
<div class="mb-3">
    <label class="form-label">Turtyp</label>
    <select name="tour_type" id="tour_type_select" class="form-select">
        @foreach($tourTypes as $tourType)
            @php
                $meta = $tourTypeMeta[$tourType->name] ?? [
                    'icon' => 'bi-bookmark-fill',
                    'badge' => 'badge-soft-secondary',
                ];
            @endphp

            <option
                value="{{ $tourType->name }}"
                data-icon="{{ $meta['icon'] }}"
                data-badge="{{ $meta['badge'] }}"
                @selected(old('tour_type', $tour->tour_type ?? $defaultTourTypeName ?? '') === $tourType->name)
            >
                {{ $tourType->name }}
            </option>
        @endforeach
    </select>

    <div class="mt-2">
        <span id="tour_type_preview" class="badge-soft badge-soft-secondary">
            <i class="bi bi-bookmark-fill me-1"></i>
            <span>Ingen turtyp vald</span>
        </span>
    </div>
</div>
<div><label>Titel</label><input name="title" value="{{ old('title',$tour->title) }}"></div>
<div><label>Datum</label><input type="date" name="tour_date" value="{{ old('tour_date',optional($tour->tour_date)->format('Y-m-d')) }}"></div>
<div><label>Guide</label><select name="guide_id"><option value="">Ingen</option>@foreach($guides as $guide)<option value="{{ $guide->id }}" @selected(old('guide_id',$tour->guide_id)==$guide->id)>{{ $guide->name }}</option>@endforeach</select></div>
<div><label>Starttid</label><input type="time" name="start_time" value="{{ old('start_time',$tour->start_time) }}"></div>
<div><label>Sluttid</label><input type="time" name="end_time" value="{{ old('end_time',$tour->end_time) }}"></div>
<div><label>Max antal</label><input type="number" name="max_participants" value="{{ old('max_participants',$tour->max_participants ?? 20) }}"></div>
<div><label>Status</label><select name="status">@foreach(['planned','started','completed','cancelled'] as $s)<option value="{{ $s }}" @selected(old('status',$tour->status ?? 'planned')==$s)>{{ $s }}</option>@endforeach</select></div>
<div style="grid-column:1 / -1"><label>Beskrivning</label><textarea name="description">{{ old('description',$tour->description) }}</textarea></div>
</div><p><button class="btn btn-primary">Spara</button></p></form>
@endsection
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tourTypeSelect = document.getElementById('tour_type_select');
    const preview = document.getElementById('tour_type_preview');

    function updateTourTypePreview() {
        if (!tourTypeSelect || !preview) return;

        const selected = tourTypeSelect.options[tourTypeSelect.selectedIndex];
        const icon = selected?.dataset.icon || 'bi-bookmark-fill';
        const badge = selected?.dataset.badge || 'badge-soft-secondary';
        const label = selected?.text || 'Ingen turtyp vald';

        preview.className = `badge-soft ${badge}`;
        preview.innerHTML = `<i class="bi ${icon} me-1"></i><span>${label}</span>`;
    }

    if (tourTypeSelect) {
        tourTypeSelect.addEventListener('change', updateTourTypePreview);
        updateTourTypePreview();
    }
});
</script>