@extends('layouts.app')
@section('content')
<h1>Felrapporter</h1>
<div class="card"><div class="card-body"><table><thead><tr><th>Rubrik</th><th>Rapporterad av</th><th>Prioritet</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($reports as $report)
<tr><td>{{ $report->title }}</td><td>{{ $report->reporter?->name }}</td><td>{{ $report->priority }}</td><td>{{ $report->status }}</td><td><a class="btn" href="{{ route('admin.reports.edit',$report) }}">Hantera</a></td></tr>
@endforeach
</tbody></table>{{ $reports->links() }}</div></div>
@endsection
