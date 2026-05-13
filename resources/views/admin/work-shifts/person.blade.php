@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();

    $usersForJs = $users->map(function ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'roles' => $user->roles->pluck('slug')->values(),
        ];
    })->values();

    $selectedUserId = request('user_id', $selectedUser?->id);
    $selectedUserRoles = $selectedUser
        ? $selectedUser->roles->pluck('slug')->values()
        : collect();

    $defaultRole = old('shift_role');

    if (!$defaultRole && $selectedUserRoles->count() === 1) {
        $defaultRole = $selectedUserRoles->first();
    }

    if (!$defaultRole) {
        $defaultRole = 'guide';
    }

    $groupedUpcomingShifts = $upcomingShifts->groupBy(function ($shift) {
        return \Carbon\Carbon::parse($shift->shift_date)->format('Y-m-d');
    });
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Schema per person</h2>
        <div class="page-subtitle">
            Välj person och lägg in kommande arbetspass. Passlistan visar närmaste pass först.
        </div>
    </div>

    <div class="page-actions">
        <a href="{{ route($prefix . '.work-shifts.index') }}" class="btn btn-outline-secondary">
            Dagvy
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Formuläret innehåller fel.</div>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="person-schedule-layout">
    <div class="page-card">
        <div class="section-title mb-3">Välj person</div>

        <form method="GET" action="{{ route($prefix . '.work-shifts.person') }}" class="mb-4">
            <label class="form-label">Person</label>
            <div class="d-flex gap-2">
                <select name="user_id" class="form-select" required>
                    <option value="">Välj person</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string) $selectedUserId === (string) $user->id)>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-primary">
                    Visa
                </button>
            </div>
        </form>

        @if($selectedUser)
            <div class="selected-person-box mb-4">
                <div class="small-muted">Vald person</div>
                <div class="fw-semibold">{{ $selectedUser->name }}</div>
                <div class="small-muted">
                    Roller:
                    {{ $selectedUser->roles->pluck('name')->implode(', ') ?: $selectedUser->roles->pluck('slug')->implode(', ') }}
                </div>
            </div>

            <div class="section-title mb-3">Lägg till arbetspass</div>

            <form method="POST" action="{{ route($prefix . '.work-shifts.person.store') }}" id="person-shift-form">
                @csrf

                <input type="hidden" name="user_id" value="{{ $selectedUser->id }}">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Roll</label>
                        <select name="shift_role" id="shift_role" class="form-select" required>
                            @foreach($shiftRoles as $key => $label)
                                @if($selectedUserRoles->contains($key))
                                    <option value="{{ $key }}" @selected($defaultRole === $key)>
                                        {{ $label }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6" id="shift_function_wrapper">
                        <label class="form-label">Restaurangfunktion</label>
                        <select name="shift_function" id="shift_function" class="form-select">
                            <option value="">Välj funktion</option>
                            @foreach($restaurantFunctions as $key => $label)
                                <option value="{{ $key }}" @selected(old('shift_function') === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Datum</label>
                        <input
                            type="date"
                            name="shift_date"
                            class="form-control"
                            value="{{ old('shift_date', now()->toDateString()) }}"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Starttid</label>
                        <input
                            type="time"
                            name="start_time"
                            class="form-control"
                            value="{{ old('start_time') }}"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Sluttid</label>
                        <input
                            type="time"
                            name="end_time"
                            class="form-control"
                            value="{{ old('end_time') }}"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', 'planned') === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Anteckning</label>
                        <input
                            type="text"
                            name="notes"
                            class="form-control"
                            value="{{ old('notes') }}"
                            placeholder="Valfritt"
                        >
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-2"></i>Lägg till pass
                        </button>
                    </div>
                </div>
            </form>
        @else
            <div class="empty-state">
                Välj en person för att lägga till arbetspass och se kommande pass.
            </div>
        @endif
    </div>

    <div class="page-card">
        <div class="section-title mb-3">
            Kommande arbetspass
            @if($selectedUser)
                – {{ $selectedUser->name }}
            @endif
        </div>

        @if(!$selectedUser)
            <div class="empty-state">
                Ingen person vald.
            </div>
        @else
            <div class="person-summary mb-3">
                <div class="person-summary-item">
                    <span>Kommande pass</span>
                    <strong>{{ $upcomingShifts->count() }}</strong>
                </div>

                <div class="person-summary-item">
                    <span>Närmast</span>
                    <strong>
                        @if($upcomingShifts->first())
                            {{ \Carbon\Carbon::parse($upcomingShifts->first()->shift_date)->format('Y-m-d') }}
                        @else
                            -
                        @endif
                    </strong>
                </div>
            </div>

            @forelse($groupedUpcomingShifts as $date => $dateShifts)
                <div class="person-shift-day">
                    <div class="person-shift-day-title">
                        {{ \Carbon\Carbon::parse($date)->format('Y-m-d') }}
                    </div>

                    <div class="person-shift-list">
                        @foreach($dateShifts as $shift)
                            <div class="person-shift-row">
                                <div>
                                    <div class="fw-semibold">
                                        {{ $shiftRoles[$shift->shift_role] ?? ucfirst($shift->shift_role) }}

                                        @if($shift->shift_role === 'restaurant' && $shift->shift_function)
                                            · {{ $restaurantFunctions[$shift->shift_function] ?? ucfirst($shift->shift_function) }}
                                        @endif
                                    </div>

                                    <div class="small-muted">
                                        {{ substr($shift->start_time, 0, 5) }}
                                        –
                                        {{ $shift->end_time ? substr($shift->end_time, 0, 5) : '--:--' }}
                                        · {{ $statuses[$shift->status] ?? ucfirst($shift->status) }}
                                    </div>

                                    @if($shift->notes)
                                        <div class="small-muted mt-1">{{ $shift->notes }}</div>
                                    @endif
                                </div>

                                <div class="person-shift-actions">
                                    <a href="{{ route($prefix . '.work-shifts.edit', $shift) }}" class="btn btn-sm btn-outline-secondary">
                                        Redigera
                                    </a>

                                    <form method="POST" action="{{ route($prefix . '.work-shifts.destroy', $shift) }}" onsubmit="return confirm('Ta bort arbetspasset?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            Ta bort
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    Inga kommande arbetspass för vald person.
                </div>
            @endforelse
        @endif
    </div>
</div>

<style>
.person-schedule-layout {
    display: grid;
    grid-template-columns: minmax(360px, 0.85fr) minmax(0, 1.25fr);
    gap: 1rem;
    align-items: start;
}

.selected-person-box {
    background: #f8fafc;
    border: 1px solid var(--brand-line-soft);
    border-radius: 12px;
    padding: 0.9rem;
}

.person-summary {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
}

.person-summary-item {
    background: #f8fafc;
    border: 1px solid var(--brand-line-soft);
    border-radius: 12px;
    padding: 0.75rem 0.85rem;
}

.person-summary-item span {
    display: block;
    color: #64748b;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.person-summary-item strong {
    display: block;
    font-size: 1.15rem;
    margin-top: 0.15rem;
}

.person-shift-day {
    border-top: 1px solid var(--brand-line-soft);
    padding-top: 1rem;
    margin-top: 1rem;
}

.person-shift-day:first-of-type {
    border-top: 0;
    padding-top: 0;
    margin-top: 0;
}

.person-shift-day-title {
    font-weight: 800;
    margin-bottom: 0.75rem;
}

.person-shift-list {
    display: grid;
    gap: 0.65rem;
}

.person-shift-row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: center;
    border: 1px solid var(--brand-line-soft);
    background: #ffffff;
    border-radius: 12px;
    padding: 0.8rem 0.9rem;
}

.person-shift-actions {
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
    justify-content: end;
}

.empty-state {
    border: 1px dashed var(--brand-line-soft);
    border-radius: 12px;
    padding: 1rem;
    color: #64748b;
    background: #f8fafc;
}

@media (max-width: 1100px) {
    .person-schedule-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 700px) {
    .person-summary {
        grid-template-columns: 1fr;
    }

    .person-shift-row {
        align-items: stretch;
        flex-direction: column;
    }

    .person-shift-actions {
        justify-content: start;
    }
}
</style>

<script>
    function toggleFunctionField() {
        const roleSelect = document.getElementById('shift_role');
        const functionWrapper = document.getElementById('shift_function_wrapper');
        const functionSelect = document.getElementById('shift_function');

        if (!roleSelect || !functionWrapper) {
            return;
        }

        const isRestaurant = roleSelect.value === 'restaurant';

        functionWrapper.style.display = isRestaurant ? '' : 'none';

        if (!isRestaurant && functionSelect) {
            functionSelect.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('shift_role');

        toggleFunctionField();

        if (roleSelect) {
            roleSelect.addEventListener('change', toggleFunctionField);
        }
    });
</script>
@endsection