@extends('layouts.app')
@section('content')
<h1>Historik: {{ $entityType }} #{{ $entityId }}</h1>
<div class="card"><div class="card-body"><table><thead><tr><th>Datum</th><th>Användare</th><th>Action</th><th>Beskrivning</th></tr></thead><tbody>
@foreach($logs as $log)
<tr><td>{{ $log->created_at->format('Y-m-d H:i') }}</td><td>{{ $log->user?->name ?? 'System' }}</td><td>{{ $log->action }}</td><td>{{ $log->description }}</td></tr>
<tr><td colspan="4"><details><summary>Visa ändringsdata</summary><pre>{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre><pre>{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details></td></tr>
@endforeach
</tbody></table>{{ $logs->links() }}</div></div>
@endsection
