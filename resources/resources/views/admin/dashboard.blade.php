@extends('layouts.app')
@section('content')
<h1>Admin / Entrévärd</h1>
<div class="grid grid-3 mb-4">
    <div class="card"><div class="card-body"><strong>Turer:</strong> {{ $tourCount }}</div></div>
    <div class="card"><div class="card-body"><strong>Bokningar:</strong> {{ $bookingCount }}</div></div>
    <div class="card"><div class="card-body"><strong>Öppna felrapporter:</strong> {{ $openReportCount }}</div></div>
</div>
<div class="card"><div class="card-body">
<h2>Kommande turer</h2>
<table><thead><tr><th>Datum</th><th>Start</th><th>Titel</th><th>Guide</th><th>Bokade</th><th>Max</th></tr></thead><tbody>
@foreach($upcomingTours as $tour)
<tr><td>{{ $tour->tour_date->format('Y-m-d') }}</td><td>{{ $tour->start_time }}</td><td>{{ $tour->title }}</td><td>{{ $tour->guide?->name }}</td><td>{{ $tour->booked_count }}</td><td>{{ $tour->max_participants }}</td></tr>
@endforeach
</tbody></table></div></div>
@endsection
