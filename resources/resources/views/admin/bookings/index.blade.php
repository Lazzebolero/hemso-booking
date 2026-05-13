@extends('layouts.app')
@section('content')
<h1>Bokningar</h1>
<p><a class="btn btn-primary" href="{{ route('admin.bookings.create') }}">Ny bokning</a></p>
<div class="card"><div class="card-body"><table><thead><tr><th>Tur</th><th>Bokning</th><th>Deltagare</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($bookings as $booking)
<tr><td>{{ $booking->tour?->title }}</td><td>{{ $booking->booking_name }}</td><td>{{ $booking->total_count }}</td><td>{{ $booking->status }}</td><td><a class="btn" href="{{ route('admin.bookings.edit',$booking) }}">Redigera</a> <a class="btn" href="{{ route('admin.activity-logs.entity-history',['booking',$booking->id]) }}">Historik</a></td></tr>
@endforeach
</tbody></table>{{ $bookings->links() }}</div></div>
@endsection
