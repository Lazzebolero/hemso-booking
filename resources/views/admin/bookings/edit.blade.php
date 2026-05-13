@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Redigera bokning</h2>
        <div class="muted">Uppdatera bokningens uppgifter, deltagarantal och status.</div>
    </div>

    <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Tillbaka
    </a>
</div>

<div class="alert alert-info">
    <strong>E-postnotifieringar:</strong>
    Om bokningen har en e-postadress kan systemet skicka bekräftelse, uppdatering eller avbokning beroende på vad som ändras.
</div>

<form method="POST" action="{{ route('admin.bookings.update', $booking) }}">
    @method('PUT')
    @include('admin.bookings.form')
</form>
@if($booking->notificationLogs->isNotEmpty())
    <div class="page-card mt-4">
        <div class="section-title">Skickade notifieringar</div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Typ</th>
                        <th>Mottagare</th>
                        <th>Ämne</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($booking->notificationLogs as $log)
                        <tr>
                            <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $log->template_key }}</td>
                            <td>{{ $log->recipient_email }}</td>
                            <td>{{ $log->subject }}</td>
                            <td>{{ $log->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection