@extends('layouts.app')
@section('content')
<h1>Mina turer</h1>
<div class="card"><div class="card-body"><table><thead><tr><th>Datum</th><th>Tid</th><th>Titel</th><th>Bokade</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($tours as $tour)
<tr><td>{{ $tour->tour_date->format('Y-m-d') }}</td><td>{{ $tour->start_time }}</td><td>{{ $tour->title }}</td><td>{{ $tour->bookings->sum('total_count') }}</td><td>{{ $tour->status }}</td><td><form class="inline" method="POST" action="{{ route('guide.tours.start',$tour) }}">@csrf<button class="btn">Starta</button></form> <form class="inline" method="POST" action="{{ route('guide.tours.complete',$tour) }}">@csrf<button class="btn">Avsluta</button></form></td></tr>
@endforeach
</tbody></table></div></div>
<h2>Mitt schema</h2>
<div class="card"><div class="card-body"><table><thead><tr><th>Datum</th><th>Titel</th><th>Tid</th><th>Typ</th></tr></thead><tbody>
@foreach($shifts as $shift)
<tr><td>{{ $shift->shift_date->format('Y-m-d') }}</td><td>{{ $shift->title }}</td><td>{{ $shift->start_time }} - {{ $shift->end_time }}</td><td>{{ $shift->shift_type }}</td></tr>
@endforeach
</tbody></table></div></div>
@endsection
