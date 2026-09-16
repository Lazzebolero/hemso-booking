@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();

    $sourceLabels = [
        \App\Models\DailyGuideOrder::SOURCE_SCHEDULE => 'Schema',
        \App\Models\DailyGuideOrder::SOURCE_MANUAL => 'Manuell',
    ];
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Dagens guider</h2>
        <div class="page-subtitle">
            Ordningen styr vilken guide som föreslås först vid batchturer och turbyten (fas 2).
            Guider med arbetspass sorteras efter passstart.
        </div>
    </div>

    <div class="page-actions">
        @if(Route::has($prefix . '.tours.batch-create'))
            <a href="{{ route($prefix . '.tours.batch-create', ['tour_date' => $selectedDate]) }}" class="btn btn-outline-secondary">
                <i class="bi bi-calendar-plus me-2"></i>Batchturer
            </a>
        @endif

        <a href="{{ route($prefix . '.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Dashboard
        </a>
    </div>
</div>

@if($wasInitialized)
    <div class="alert alert-info">
        Listan skapades automatiskt från dagens guidepass.
    </div>
@endif

<div class="page-card mb-4">
    <form method="GET" action="{{ route($prefix . '.daily-guide-orders.index') }}" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Datum</label>
            <input type="date" name="date" class="form-control" value="{{ $selectedDate }}" required>
        </div>

        <div class="col-md-8">
            <button type="submit" class="btn btn-primary">
                Visa dag
            </button>
        </div>
    </form>

    <form method="POST" action="{{ route($prefix . '.daily-guide-orders.sync-schedule') }}" class="mt-3">
        @csrf
        <input type="hidden" name="date" value="{{ $selectedDate }}">
        <button type="submit" class="btn btn-outline-primary">
            <i class="bi bi-arrow-repeat me-2"></i>Hämta från schema
        </button>
    </form>
</div>

<div class="page-card mb-4">
    <div class="section-title mb-3">Guideordning {{ $selectedDate }}</div>

    @if($orders->isEmpty())
        <div class="text-center muted py-4">
            Inga guider i listan för detta datum.
            Lägg till manuellt nedan eller schemalägg guidepass och klicka <strong>Hämta från schema</strong>.
        </div>
    @else
        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th style="width: 90px;">Ordning</th>
                        <th>Guide</th>
                        <th style="width: 120px;">Språk</th>
                        <th>Passstart</th>
                        <th>Källa</th>
                        <th style="width: 180px;">Ändra ordning</th>
                        <th style="width: 90px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                        @php
                            $shift = $shiftStarts->get($order->user_id);
                            $shiftStart = $shift?->start_time ? substr($shift->start_time, 0, 5) : null;
                        @endphp

                        <tr>
                            <td>
                                <span class="order-badge">{{ $order->sort_order }}</span>
                            </td>

                            <td>{{ $order->user?->name ?? 'Okänd guide' }}</td>

                            <td>{{ $order->user?->guideLanguageLabel() ?? '-' }}</td>

                            <td>
                                @if($shiftStart)
                                    {{ $shiftStart }}
                                @else
                                    <span class="muted">Inget pass</span>
                                @endif
                            </td>

                            <td>
                                <span class="badge-soft {{ $order->source === \App\Models\DailyGuideOrder::SOURCE_MANUAL ? 'badge-soft-warning' : 'badge-soft-secondary' }}">
                                    {{ $sourceLabels[$order->source] ?? $order->source }}
                                </span>
                            </td>

                            <td>
                                <div class="d-flex gap-1">
                                    <form method="POST" action="{{ route($prefix . '.daily-guide-orders.move', $order) }}">
                                        @csrf
                                        <input type="hidden" name="direction" value="up">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" @disabled($loop->first) title="Flytta upp">
                                            <i class="bi bi-arrow-up"></i>
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route($prefix . '.daily-guide-orders.move', $order) }}">
                                        @csrf
                                        <input type="hidden" name="direction" value="down">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" @disabled($loop->last) title="Flytta ner">
                                            <i class="bi bi-arrow-down"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>

                            <td>
                                <form method="POST" action="{{ route($prefix . '.daily-guide-orders.destroy', $order) }}" onsubmit="return confirm('Ta bort guiden från dagens lista?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Ta bort">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="page-card">
    <div class="section-title mb-3">Lägg till guide manuellt</div>

    @if($availableGuides->isEmpty())
        <div class="muted">Alla guider finns redan i listan, eller inga guider med rollen guide hittades.</div>
    @else
        <form method="POST" action="{{ route($prefix . '.daily-guide-orders.store') }}" class="row g-3 align-items-end">
            @csrf
            <input type="hidden" name="date" value="{{ $selectedDate }}">

            <div class="col-md-6">
                <label class="form-label">Guide</label>
                <select name="user_id" class="form-select" required>
                    <option value="">Välj guide</option>
                    @foreach($availableGuides as $guide)
                        <option value="{{ $guide->id }}" @selected(old('user_id') == $guide->id)>
                            {{ $guide->name }}
                        </option>
                    @endforeach
                </select>
                @error('user_id')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-person-plus me-2"></i>Lägg till
                </button>
            </div>
        </form>
    @endif
</div>

<style>
.order-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2rem;
    min-height: 2rem;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-weight: 800;
}
</style>
@endsection
