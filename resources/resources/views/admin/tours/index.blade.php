@extends('layouts.app')
@section('content')
<h1>Turer</h1>
<p><a class="btn btn-primary" href="{{ route('admin.tours.create') }}">Ny tur</a></p>
<div class="card"><div class="card-body"><table><thead><tr><th>Datum</th><th>Tid</th><th>Titel</th><th>Guide</th><th>Bokade</th><th>Max</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($tours as $tour)
<tr>
<td>{{ $tour->tour_date->format('Y-m-d') }}</td><td>{{ $tour->start_time }}</td><td>{{ $tour->title }}</td><td>{{ $tour->guide?->name }}</td><td>{{ $tour->booked_count }}</td><td>{{ $tour->max_participants }}</td><td>{{ $tour->status }}</td>
<td>
<a class="btn" href="{{ route('admin.tours.edit',$tour) }}">Redigera</a>
<form class="inline" method="POST" action="{{ route('admin.tours.start',$tour) }}">@csrf<button class="btn">Starta</button></form>
<form class="inline" method="POST" action="{{ route('admin.tours.complete',$tour) }}">@csrf<button class="btn">Avsluta</button></form>
<a class="btn" href="{{ route('admin.activity-logs.entity-history',['tour',$tour->id]) }}">Historik</a>
</td></tr>
@endforeach
</tbody></table>{{ $tours->links() }}</div></div>
@endsection
