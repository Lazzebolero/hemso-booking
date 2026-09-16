@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Ekonomi</h2>
        <div class="page-subtitle">
            Standardvärden för lönsamhetsanalys — kostnad per timme (guide/värd och restaurang) och biljettpriser.
        </div>
    </div>
    <div class="page-actions">
        @if(Route::has('admin.economy-profitability.index'))
            <a href="{{ route('admin.economy-profitability.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-graph-up-arrow me-2"></i>Guidning – lönsamhet
            </a>
        @endif
        @if(Route::has('admin.restaurant-economy-cost.index'))
            <a href="{{ route('admin.restaurant-economy-cost.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-cup-hot me-2"></i>Restaurangkostnader
            </a>
        @endif
        <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-gear me-2"></i>Systeminställningar
        </a>
    </div>
</div>

@include('partials.ui.flash-messages')

<form method="POST" action="{{ route('admin.economy-settings.update') }}">
    @csrf
    @method('PUT')

    <div class="admin-grid-2">
        <div class="page-card">
            <div class="section-title">Kostnad</div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="economy_guide_hourly_cost">Timkostnad guide/värd (SEK)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        id="economy_guide_hourly_cost"
                        name="economy_guide_hourly_cost"
                        class="form-control @error('economy_guide_hourly_cost') is-invalid @enderror"
                        value="{{ old('economy_guide_hourly_cost', $settings['economy_guide_hourly_cost']) }}"
                        required
                    >
                    @error('economy_guide_hourly_cost')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Genomsnittlig lönekostnad per timme för guide- och värdpass. Används vid kostnadsberäkning.</div>
                </div>

                <div class="col-12">
                    <label class="form-label" for="economy_restaurant_hourly_cost">Timkostnad restaurang (SEK)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        id="economy_restaurant_hourly_cost"
                        name="economy_restaurant_hourly_cost"
                        class="form-control @error('economy_restaurant_hourly_cost') is-invalid @enderror"
                        value="{{ old('economy_restaurant_hourly_cost', $settings['economy_restaurant_hourly_cost']) }}"
                        required
                    >
                    @error('economy_restaurant_hourly_cost')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Genomsnittlig lönekostnad per timme för restaurangpass.</div>
                </div>

                <div class="col-12">
                    <label class="form-label" for="economy_ob_hourly_amount">OB-tillägg (SEK/timme)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        id="economy_ob_hourly_amount"
                        name="economy_ob_hourly_amount"
                        class="form-control @error('economy_ob_hourly_amount') is-invalid @enderror"
                        value="{{ old('economy_ob_hourly_amount', $settings['economy_ob_hourly_amount']) }}"
                        required
                    >
                    @error('economy_ob_hourly_amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">
                        Ordinarie OB-ersättning per timme under obekväm arbetstid.
                    </div>
                    <div class="mt-2 small-muted">
                        <div class="fw-semibold mb-1">Gällande OB-tider</div>
                        <ul class="mb-0 ps-3">
                            @foreach(($obWindows ?? []) as $window)
                                <li>
                                    <strong>{{ $window['label'] }}:</strong>
                                    {{ $window['description'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-card">
            <div class="section-title">Intäkter / biljettpriser</div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="economy_price_adult">Pris vuxen (män/kvinnor)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        id="economy_price_adult"
                        name="economy_price_adult"
                        class="form-control @error('economy_price_adult') is-invalid @enderror"
                        value="{{ old('economy_price_adult', $settings['economy_price_adult']) }}"
                        required
                    >
                    @error('economy_price_adult')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="economy_price_youth">Pris ungdom</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        id="economy_price_youth"
                        name="economy_price_youth"
                        class="form-control @error('economy_price_youth') is-invalid @enderror"
                        value="{{ old('economy_price_youth', $settings['economy_price_youth']) }}"
                        required
                    >
                    @error('economy_price_youth')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="economy_price_child">Pris barn</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        id="economy_price_child"
                        name="economy_price_child"
                        class="form-control @error('economy_price_child') is-invalid @enderror"
                        value="{{ old('economy_price_child', $settings['economy_price_child']) }}"
                        required
                    >
                    @error('economy_price_child')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="economy_child_under4_percent">Andel barn under 4 (%)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        id="economy_child_under4_percent"
                        name="economy_child_under4_percent"
                        class="form-control @error('economy_child_under4_percent') is-invalid @enderror"
                        value="{{ old('economy_child_under4_percent', $settings['economy_child_under4_percent']) }}"
                        required
                    >
                    @error('economy_child_under4_percent')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">
                        Barn under 4 går in gratis. Andelen används vid intäktsberäkning
                        (betalande barn = barn × (1 − andel/100)).
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-card mt-4">
        <div class="section-title">Notifiering</div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="economics_notification_email">E-post till ekonomi</label>
                <input
                    type="email"
                    id="economics_notification_email"
                    name="economics_notification_email"
                    class="form-control @error('economics_notification_email') is-invalid @enderror"
                    value="{{ old('economics_notification_email', $settings['economics_notification_email']) }}"
                    placeholder="ekonomi@example.se"
                >
                @error('economics_notification_email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">Får meddelande när en bokning skapas med &quot;Faktureras&quot;.</div>
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-2"></i>Spara ekonomiinställningar
            </button>
        </div>
    </div>
</form>
@endsection
