@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Specialturer</h2>
        <div class="page-subtitle">
            Här skapar du specialturer med bokningsformulär som besökare själva kan boka. Exempelvis turer där alla 3 anläggningar visas.
            Funktionen skapar tur, bokningsformulär och en unik länk till det publika formuläret.
        </div>
    </div>

    <div class="page-actions">
        <a href="{{ route($prefix . '.special-tours.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Ny specialtur
        </a>
    </div>
</div>

<div class="page-card compact-card">
    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Tur</th>
                    <th style="width: 120px;">Datum</th>
                    <th style="width: 90px;">Tid</th>
                    <th style="width: 140px;">Guide</th>
                    <th style="width: 110px;">Publik</th>
                    <th>URL</th>
                    <th style="width: 120px;">Åtgärder</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tours as $tour)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $tour->title }}</div>
                            <div class="small-muted">{{ $tour->tourType?->name ?? '-' }}</div>
                        </td>
                        <td>{{ $tour->tour_date ? \Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d') : '-' }}</td>
                        <td>{{ $tour->start_time ? substr($tour->start_time, 0, 5) : '-' }}</td>
                        <td>{{ $tour->guide?->name ?? 'Ej tilldelad' }}</td>
                        <td>
                            @if($tour->bookingPage?->is_public)
                                <span class="badge-soft badge-soft-success">Ja</span>
                            @else
                                <span class="badge-soft badge-soft-secondary">Nej</span>
                            @endif
                        </td>
                        <td>
                            @if($tour->bookingPage?->slug)
                                <a href="{{ route('public.tour-booking.show', $tour->bookingPage->slug) }}" target="_blank">
                                    {{ route('public.tour-booking.show', $tour->bookingPage->slug) }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <a href="{{ route($prefix . '.special-tours.edit', $tour) }}" class="btn btn-sm btn-outline-secondary">
                                Redigera
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center muted py-4">Inga specialturer hittades.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($tours, 'links'))
        <div class="mt-3">
            {{ $tours->links() }}
        </div>
    @endif
</div>
@endsection