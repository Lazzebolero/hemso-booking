@extends('layouts.app')
@section('content')
<h1>Hantera felrapport</h1>
<div class="card"><div class="card-body"><p><strong>{{ $report->title }}</strong></p><p>{{ $report->description }}</p><p>Plats: {{ $report->location }}</p></div></div>
<form method="POST" action="{{ route('admin.reports.update',$report) }}">@csrf @method('PUT')
<div class="grid grid-3">
<div><label>Status</label><select name="status">@foreach(['new','in_progress','waiting_action','resolved','closed'] as $s)<option value="{{ $s }}" @selected(old('status',$report->status)==$s)>{{ $s }}</option>@endforeach</select></div>
<div><label>Tilldelad</label><select name="assigned_to"><option value="">Ingen</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('assigned_to',$report->assigned_to)==$user->id)>{{ $user->name }}</option>@endforeach</select></div>
<div style="grid-column:1 / -1"><label>Intern kommentar</label><textarea name="internal_comment">{{ old('internal_comment',$report->internal_comment) }}</textarea></div>
</div><p><button class="btn btn-primary">Spara</button></p></form>
@endsection
