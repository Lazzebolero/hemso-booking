@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Bokningar</h2>
        <div class="page-subtitle">Översikt över bokningar och deras status.</div>
    </div>

    <div class="page-actions">
        <a class="btn btn-primary" href="{{ route($prefix . '.bookings.create') }}">
            <i class="bi bi-plus-circle me-2"></i>Ny bokning
        </a>

        <a class="btn btn-outline-secondary" href="{{ route($prefix . '.bookings.quick-create') }}">
            <i class="bi bi-lightning-charge me-2"></i>Snabbbokning
        </a>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Tur</th>
                    <th>Bokning</th>
                    <th>Deltagare</th>
                    <th>Status</th>
                    <th style="width: 240px;">Åtgärder</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $booking)
                    <tr>
                        <td>{{ $booking->tour?->title ?? '—' }}</td>
                        <td>{{ $booking->booking_name ?? '—' }}</td>
                        <td>{{ $booking->total_count ?? 0 }}</td>
                        <td>{{ $booking->status ?? '—' }}</td>
                        <td>
                            <div class="toolbar-inline">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route($prefix . '.bookings.edit', $booking) }}">
                                    Redigera
                                </a>

                                <a class="btn btn-sm btn-outline-secondary" href="{{ route($prefix . '.activity-logs.entity-history', ['booking', $booking->id]) }}">
                                    Historik
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Inga bokningar hittades.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $bookings->links() }}
    </div>
</div>
@endsection