@extends('layouts.app')
@section('content')
<h1>{{ $shift->exists ? 'Redigera schemapost' : 'Ny schemapost' }}</h1>
<form method="POST" action="{{ $shift->exists ? route('admin.shifts.update',$shift) : route('admin.shifts.store') }}">@csrf @if($shift->exists) @method('PUT') @endif
<div class="grid grid-3">
<div><label>Guide</label><select name="guide_id">@foreach($guides as $guide)<option value="{{ $guide->id }}" @selected(old('guide_id',$shift->guide_id)==$guide->id)>{{ $guide->name }}</option>@endforeach</select></div>
<div><label>Tur (valfri)</label><select name="tour_id"><option value="">Ingen</option>@foreach($tours as $tour)<option value="{{ $tour->id }}" @selected(old('tour_id',$shift->tour_id)==$tour->id)>{{ $tour->title }}</option>@endforeach</select></div>
<div><label>Typ</label><select name="shift_type">@foreach(['tour','work_shift','meeting','maintenance','blocked'] as $t)<option value="{{ $t }}" @selected(old('shift_type',$shift->shift_type ?? 'tour')==$t)>{{ $t }}</option>@endforeach</select></div>
<div><label>Titel</label><input name="title" value="{{ old('title',$shift->title) }}"></div>
<div><label>Datum</label><input type="date" name="shift_date" value="{{ old('shift_date',optional($shift->shift_date)->format('Y-m-d')) }}"></div>
<div><label>Start</label><input type="time" name="start_time" value="{{ old('start_time',$shift->start_time) }}"></div>
<div><label>Slut</label><input type="time" name="end_time" value="{{ old('end_time',$shift->end_time) }}"></div>
<div style="grid-column:1 / -1"><label>Notering</label><textarea name="notes">{{ old('notes',$shift->notes) }}</textarea></div>
</div><p><button class="btn btn-primary">Spara</button></p></form>
@endsection
