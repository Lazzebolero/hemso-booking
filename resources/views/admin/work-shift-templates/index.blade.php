@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
    $weekdayOptions = \App\Models\WorkShiftTemplate::weekdayOptions();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Schemamallar</h2>
        <div class="page-subtitle">Skapa återkommande mallar och generera schema för vecka.</div>
    </div>
</div>

<div class="page-card mb-4">
    <div class="section-title mb-3">Generera vecka från aktiva mallar</div>

    <form method="POST" action="{{ route($prefix . '.work-shift-templates.generate') }}" class="row g-3 align-items-end">
        @csrf
        <div class="col-md-4">
            <label class="form-label">Utgå från datum i veckan</label>
            <input type="date" name="start_date" class="form-control" value="{{ now()->toDateString() }}" required>
        </div>
        <div class="col-md-4">
            <button class="btn btn-primary">Generera vecka</button>
        </div>
    </form>
</div>

<div class="page-card mb-4">
    <div class="section-title mb-3">Ny schemamall</div>

    <form method="POST" action="{{ route($prefix . '.work-shift-templates.store') }}">
        @csrf

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Användare</label>
                <select name="user_id" class="form-select" required>
                    <option value="">Välj användare</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Veckodag</label>
                <select name="weekday" class="form-select" required>
                    <option value="">Välj dag</option>
                    @foreach($weekdayOptions as $key => $label)
                        <option value="{{ $key }}" @selected((string) old('weekday') === (string) $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Starttid</label>
                <input type="time" name="start_time" class="form-control" value="{{ old('start_time') }}" required>
            </div>

            <div class="col-md-2">
                <label class="form-label">Sluttid</label>
                <input type="time" name="end_time" class="form-control" value="{{ old('end_time') }}">
            </div>

            <div class="col-md-2">
                <label class="form-label">Roll</label>
                <select name="shift_role" id="shift_role_create" class="form-select" required>
                    <option value="">Välj roll</option>
                    @foreach($shiftRoles as $key => $label)
                        <option value="{{ $key }}" @selected(old('shift_role') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3" id="shift_function_create_wrapper">
                <label class="form-label">Funktion</label>
                <select name="shift_function" id="shift_function_create" class="form-select">
                    <option value="">Ingen funktion</option>
                    @foreach($restaurantFunctions as $key => $label)
                        <option value="{{ $key }}" @selected(old('shift_function') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(old('status', 'planned') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active_create" @checked(old('is_active', true))>
                    <label class="form-check-label" for="is_active_create">
                        Aktiv mall
                    </label>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label">Anteckning</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
            </div>

            <div class="col-12">
                <button class="btn btn-primary">Spara schemamall</button>
            </div>
        </div>
    </form>
</div>

<div class="page-card">
    <div class="section-title mb-3">Befintliga mallar</div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Veckodag</th>
                    <th>Person</th>
                    <th>Start</th>
                    <th>Slut</th>
                    <th>Roll</th>
                    <th>Funktion</th>
                    <th>Status</th>
                    <th>Aktiv</th>
                    <th>Anteckning</th>
                    <th style="width: 240px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $template)
                    <tr>
                        <td>{{ $weekdayOptions[$template->weekday] ?? $template->weekday }}</td>
                        <td>{{ $template->user?->name }}</td>
                        <td>{{ substr($template->start_time, 0, 5) }}</td>
                        <td>{{ $template->end_time ? substr($template->end_time, 0, 5) : '-' }}</td>
                        <td>{{ $shiftRoles[$template->shift_role] ?? ucfirst($template->shift_role) }}</td>
                        <td>
                            @if($template->shift_function)
                                {{ $restaurantFunctions[$template->shift_function] ?? ucfirst($template->shift_function) }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $statuses[$template->status] ?? ucfirst($template->status) }}</td>
                        <td>{{ $template->is_active ? 'Ja' : 'Nej' }}</td>
                        <td>{{ $template->notes ?: '-' }}</td>
                        <td>
                            <details>
                                <summary class="btn btn-sm btn-outline-secondary">Redigera</summary>

                                <div class="mt-3">
                                    <form method="POST" action="{{ route($prefix . '.work-shift-templates.update', $template) }}">
                                        @csrf
                                        @method('PUT')

                                        <div class="row g-2">
                                            <div class="col-12">
                                                <label class="form-label">Användare</label>
                                                <select name="user_id" class="form-select" required>
                                                    @foreach($users as $user)
                                                        <option value="{{ $user->id }}" @selected((string) $template->user_id === (string) $user->id)>
                                                            {{ $user->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Veckodag</label>
                                                <select name="weekday" class="form-select" required>
                                                    @foreach($weekdayOptions as $key => $label)
                                                        <option value="{{ $key }}" @selected((string) $template->weekday === (string) $key)>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Roll</label>
                                                <select name="shift_role" class="form-select template-role-select" required data-target="template-function-{{ $template->id }}">
                                                    @foreach($shiftRoles as $key => $label)
                                                        <option value="{{ $key }}" @selected($template->shift_role === $key)>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Starttid</label>
                                                <input type="time" name="start_time" class="form-control" value="{{ substr($template->start_time, 0, 5) }}" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Sluttid</label>
                                                <input type="time" name="end_time" class="form-control" value="{{ $template->end_time ? substr($template->end_time, 0, 5) : '' }}">
                                            </div>

                                            <div class="col-md-6" id="template-function-{{ $template->id }}">
                                                <label class="form-label">Funktion</label>
                                                <select name="shift_function" class="form-select">
                                                    <option value="">Ingen funktion</option>
                                                    @foreach($restaurantFunctions as $key => $label)
                                                        <option value="{{ $key }}" @selected($template->shift_function === $key)>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-select" required>
                                                    @foreach($statuses as $key => $label)
                                                        <option value="{{ $key }}" @selected($template->status === $key)>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label">Anteckning</label>
                                                <textarea name="notes" class="form-control" rows="2">{{ $template->notes }}</textarea>
                                            </div>

                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active_{{ $template->id }}" @checked($template->is_active)>
                                                    <label class="form-check-label" for="is_active_{{ $template->id }}">
                                                        Aktiv mall
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="col-12 d-flex gap-2">
                                                <button class="btn btn-sm btn-primary">Spara</button>
                                    </form>

                                    <form method="POST" action="{{ route($prefix . '.work-shift-templates.destroy', $template) }}" onsubmit="return confirm('Ta bort schemamallen?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Ta bort</button>
                                    </form>
                                            </div>
                                        </div>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">Inga schemamallar finns ännu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    function toggleRestaurantFunction(selectElement, wrapperId) {
        const wrapper = document.getElementById(wrapperId);
        if (!wrapper) {
            return;
        }

        const isRestaurant = selectElement.value === 'restaurant';
        wrapper.style.display = isRestaurant ? '' : 'none';

        const functionSelect = wrapper.querySelector('select');
        if (!isRestaurant && functionSelect) {
            functionSelect.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const createRoleSelect = document.getElementById('shift_role_create');
        if (createRoleSelect) {
            const updateCreate = function () {
                toggleRestaurantFunction(createRoleSelect, 'shift_function_create_wrapper');
            };

            createRoleSelect.addEventListener('change', updateCreate);
            updateCreate();
        }

        document.querySelectorAll('.template-role-select').forEach(function (select) {
            const targetId = select.dataset.target;

            const updateTemplate = function () {
                toggleRestaurantFunction(select, targetId);
            };

            select.addEventListener('change', updateTemplate);
            updateTemplate();
        });
    });
</script>
@endsection