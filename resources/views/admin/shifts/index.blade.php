@extends('layouts.app')
@section('content')
<h1>Schema</h1>
<p><a class="btn btn-primary" href="{{ route('admin.shifts.create') }}">Ny schemapost</a></p>
<div class="card"><div class="card-body"><table><thead><tr><th>Datum</th><th>Guide</th><th>Titel</th><th>Typ</th><th>Tid</th><th></th></tr></thead><tbody>
@foreach($shifts as $shift)
<tr><td>{{ $shift->shift_date->format('Y-m-d') }}</td><td>{{ $shift->guide?->name }}</td><td>{{ $shift->title }}</td><td>{{ $shift->shift_type }}</td><td>{{ $shift->start_time }} - {{ $shift->end_time }}</td><td><a class="btn" href="{{ route('admin.shifts.edit',$shift) }}">Redigera</a></td></tr>
@endforeach
</tbody></table>{{ $shifts->links() }}</div></div>
@endsection
