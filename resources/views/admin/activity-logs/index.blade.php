@extends('layouts.app')
@section('content')
<h1>Aktivitetslogg</h1>
<form method="GET" class="card mb-4"><div class="card-body"><div class="grid grid-3">
<div><label>Datum från</label><input type="date" name="date_from" value="{{ request('date_from') }}"></div>
<div><label>Datum till</label><input type="date" name="date_to" value="{{ request('date_to') }}"></div>
<div><label>Användare</label><select name="user_id"><option value="">Alla</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(request('user_id')==$user->id)>{{ $user->name }}</option>@endforeach</select></div>
<div><label>Typ</label><select name="entity_type"><option value="">Alla</option>@foreach($entityTypes as $type)<option value="{{ $type }}" @selected(request('entity_type')==$type)>{{ $type }}</option>@endforeach</select></div>
<div><label>Action</label><select name="action"><option value="">Alla</option>@foreach($actions as $action)<option value="{{ $action }}" @selected(request('action')==$action)>{{ $action }}</option>@endforeach</select></div>
<div><label>Objekt-ID</label><input type="number" name="entity_id" value="{{ request('entity_id') }}"></div>
</div><p><button class="btn btn-primary">Filtrera</button></p></div></form>
<div class="card"><div class="card-body"><table><thead><tr><th>Datum</th><th>Användare</th><th>Typ</th><th>ID</th><th>Action</th><th>Beskrivning</th><th></th></tr></thead><tbody>
@foreach($logs as $log)
<tr><td>{{ $log->created_at->format('Y-m-d H:i') }}</td><td>{{ $log->user?->name ?? 'System' }}</td><td>{{ $log->entity_type }}</td><td>{{ $log->entity_id }}</td><td>{{ $log->action }}</td><td>{{ $log->description }}</td><td>@if($log->entity_id)<a class="btn" href="{{ route('admin.activity-logs.entity-history',[$log->entity_type,$log->entity_id]) }}">Historik</a>@endif</td></tr>
@endforeach
</tbody></table>{{ $logs->links() }}</div></div>
@endsection
