@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
    $fromTour = $fromTour ?? false;
    $isRetroactiveBooking = $isRetroactiveBooking ?? false;
    $backUrl = $fromTour && $booking->tour
        ? route($prefix . '.tours.show', $booking->tour)
        : route($prefix . '.bookings.index', $isRetroactiveBooking ? ['scope' => 'archive'] : []);
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Redigera bokning</h2>
        <div class="page-subtitle">Uppdatera bokningens uppgifter, deltagarantal och status.</div>
    </div>

    <div class="page-actions">
        <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

@if($isRetroactiveBooking)
    <div class="page-card compact-card mb-3">
        <div class="small-muted">
            Turen är avslutad eller historisk. Du kan rätta bokningen i efterhand utan kapacitetsgräns och utan att bekräftelsemail skickas automatiskt.
        </div>
    </div>
@endif

<div class="page-card compact-card mb-3">
    <div class="info-label mb-2">Ändringshistorik</div>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="small-muted mb-1">Skapad</div>
            <div>
                {{ $booking->created_at?->format('Y-m-d H:i') ?? '–' }}
                · {{ ($createdByUser ?? null)?->name ?? 'Okänd användare' }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="small-muted mb-1">Senast ändrad</div>
            <div>
                {{ $booking->updated_at?->format('Y-m-d H:i') ?? '–' }}
                · {{ ($updatedByUser ?? null)?->name ?? 'Okänd användare' }}
            </div>
        </div>
    </div>
</div>

<div class="page-card compact-card mb-3">
    <div class="info-label mb-2">E-postnotifieringar</div>
    <div class="small-muted">
        Om bokningen har en e-postadress kan systemet skicka bekräftelse, uppdatering eller avbokning beroende på vad som ändras.
    </div>
</div>

<form method="POST" action="{{ route($prefix . '.bookings.update', $booking) }}">
    @csrf
    @method('PUT')
    @if($fromTour)
        <input type="hidden" name="from_tour" value="1">
    @endif
    @include('admin.bookings.form')
</form>

<div class="page-card mt-4">
    <div class="section-title mb-2">Ta bort bokning</div>
    <div class="small-muted mb-3">
        Bokningen raderas permanent från systemet. Detta skiljer sig från att sätta status till avbokad.
    </div>

    @include('partials.bookings.delete-form', [
        'booking' => $booking,
        'prefix' => $prefix,
        'fromTour' => $fromTour,
        'scope' => $isRetroactiveBooking ? 'archive' : 'active',
        'buttonClass' => 'btn btn-outline-danger',
    ])
</div>

@if($booking->notificationLogs->isNotEmpty())
    <div class="page-card mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="section-title mb-0">Skickade notifieringar</div>
            <div class="small-muted">{{ $booking->notificationLogs->count() }} utskick</div>
        </div>

        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th style="width: 155px;">Datum</th>
                        <th style="width: 130px;">Typ</th>
                        <th>Mottagare</th>
                        <th>Ämne</th>
                        <th style="width: 110px;">Status</th>
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
