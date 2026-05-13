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

    $groupedShifts = $shifts->groupBy(function ($shift) {
        if ($shift->shift_role === 'restaurant') {
            return 'restaurant:' . ($shift->shift_function ?: 'ovrigt');
        }

        return $shift->shift_role;
    });

    $groupLabels = [
        'admin' => 'Admin',
        'host' => 'Värd',
        'guide' => 'Guide',
    ];
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Arbetsschema</h2>
        <div class="page-subtitle">
            Lägg in bemanning per dag. Välj roll, person, eventuell restaurangfunktion och arbetstid.
        </div>
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

<div class="page-card mb-4">
    <form method="GET" action="{{ route($prefix . '.work-shifts.index') }}" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Datum</label>
            <input
                type="date"
                name="date"
                class="form-control"
                value="{{ $selectedDate->toDateString() }}"
            >
        </div>

        <div class="col-md-8">
            <div class="d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">
                    Visa dag
                </button>

                <a href="{{ route($prefix . '.work-shifts.index', ['date' => now()->toDateString()]) }}" class="btn btn-outline-secondary">
                    Idag
                </a>

                <a href="{{ route($prefix . '.work-shifts.index', ['date' => $selectedDate->copy()->addDay()->toDateString()]) }}" class="btn btn-outline-secondary">
                    Nästa dag
                </a>

                <a href="{{ route($prefix . '.work-shifts.index', ['date' => $selectedDate->copy()->subDay()->toDateString()]) }}" class="btn btn-outline-secondary">
                    Föregående dag
                </a>
            </div>
        </div>
    </form>
</div>

<div class="schedule-day-layout">
    <div class="page-card">
        <div class="section-title mb-3">Lägg till arbetspass</div>

        <form method="POST" action="{{ route($prefix . '.work-shifts.store') }}" id="day-shift-form">
            @csrf

            <input type="hidden" name="shift_date" value="{{ $selectedDate->toDateString() }}">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Roll</label>
                    <select name="shift_role" id="shift_role" class="form-select" required>
                        @foreach($shiftRoles as $key => $label)
                            <option value="{{ $key }}" @selected(old('shift_role', 'guide') === $key)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-8">
                    <label class="form-label">Person</label>
                    <select name="user_id" id="user_id" class="form-select" required>
                        <option value="">Välj person</option>
                    </select>
                    <div class="form-text">Listan filtreras efter vald roll.</div>
                </div>

                <div class="col-md-4" id="shift_function_wrapper">
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
    </div>

    <div class="page-card">
        <div class="section-title mb-3">
            Bemanning {{ $selectedDate->format('Y-m-d') }}
        </div>

        <div class="staffing-summary mb-3">
            <div class="staffing-summary-item">
                <span>Totalt</span>
                <strong>{{ $shifts->count() }}</strong>
            </div>
            <div class="staffing-summary-item">
                <span>Guider</span>
                <strong>{{ $shifts->where('shift_role', 'guide')->count() }}</strong>
            </div>
            <div class="staffing-summary-item">
                <span>Värdar</span>
                <strong>{{ $shifts->where('shift_role', 'host')->count() }}</strong>
            </div>
            <div class="staffing-summary-item">
                <span>Restaurang</span>
                <strong>{{ $shifts->where('shift_role', 'restaurant')->count() }}</strong>
            </div>
        </div>

        @forelse($groupedShifts as $groupKey => $groupShifts)
            @php
                if (str_starts_with($groupKey, 'restaurant:')) {
                    $functionKey = str_replace('restaurant:', '', $groupKey);
                    $groupTitle = 'Restaurang – ' . ($restaurantFunctions[$functionKey] ?? ucfirst($functionKey));
                } else {
                    $groupTitle = $groupLabels[$groupKey] ?? ucfirst($groupKey);
                }
            @endphp

            <div class="shift-group">
                <div class="shift-group-title">{{ $groupTitle }}</div>

                <div class="shift-list">
                    @foreach($groupShifts as $shift)
                        <div class="shift-row">
                            <div>
                                <div class="fw-semibold">{{ $shift->user?->name }}</div>
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

                            <div class="shift-actions">
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
                Inga arbetspass inlagda för denna dag.
            </div>
        @endforelse
    </div>
</div>

<style>
.schedule-day-layout {
    display: grid;
    grid-template-columns: minmax(360px, 0.9fr) minmax(0, 1.3fr);
    gap: 1rem;
    align-items: start;
}

.staffing-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.75rem;
}

.staffing-summary-item {
    background: #f8fafc;
    border: 1px solid var(--brand-line-soft);
    border-radius: 12px;
    padding: 0.75rem 0.85rem;
}

.staffing-summary-item span {
    display: block;
    color: #64748b;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.staffing-summary-item strong {
    display: block;
    font-size: 1.35rem;
    margin-top: 0.15rem;
}

.shift-group {
    border-top: 1px solid var(--brand-line-soft);
    padding-top: 1rem;
    margin-top: 1rem;
}

.shift-group:first-of-type {
    border-top: 0;
    padding-top: 0;
    margin-top: 0;
}

.shift-group-title {
    font-weight: 800;
    margin-bottom: 0.75rem;
}

.shift-list {
    display: grid;
    gap: 0.65rem;
}

.shift-row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: center;
    border: 1px solid var(--brand-line-soft);
    background: #ffffff;
    border-radius: 12px;
    padding: 0.8rem 0.9rem;
}

.shift-actions {
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
    .schedule-day-layout {
        grid-template-columns: 1fr;
    }

    .staffing-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .staffing-summary {
        grid-template-columns: 1fr;
    }

    .shift-row {
        align-items: stretch;
        flex-direction: column;
    }

    .shift-actions {
        justify-content: start;
    }
}
</style>

<script>
    const users = @json($usersForJs);
    const oldUserId = @json((string) old('user_id', ''));
    const oldRole = @json((string) old('shift_role', 'guide'));

    function fillUserOptions() {
        const roleSelect = document.getElementById('shift_role');
        const userSelect = document.getElementById('user_id');
        const functionWrapper = document.getElementById('shift_function_wrapper');
        const functionSelect = document.getElementById('shift_function');

        if (!roleSelect || !userSelect) {
            return;
        }

        const selectedRole = roleSelect.value;

        userSelect.innerHTML = '';

        const empty = document.createElement('option');
        empty.value = '';
        empty.textContent = 'Välj person';
        userSelect.appendChild(empty);

        users
            .filter(user => user.roles.includes(selectedRole))
            .forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = user.name;

                if (String(user.id) === oldUserId) {
                    option.selected = true;
                }

                userSelect.appendChild(option);
            });

        const isRestaurant = selectedRole === 'restaurant';

        if (functionWrapper) {
            functionWrapper.style.display = isRestaurant ? '' : 'none';
        }

        if (!isRestaurant && functionSelect) {
            functionSelect.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('shift_role');

        if (roleSelect && oldRole) {
            roleSelect.value = oldRole;
        }

        fillUserOptions();

        if (roleSelect) {
            roleSelect.addEventListener('change', fillUserOptions);
        }
    });
</script>
@endsection