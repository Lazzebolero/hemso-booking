@extends('layouts.app')

@section('content')
@php
    $selectedRoles = collect(
        old(
            'roles',
            $user->exists && $user->relationLoaded('roles')
                ? $user->roles->pluck('slug')->all()
                : []
        )
    )->map(fn ($role) => (string) $role)->all();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">
            {{ $user->exists ? 'Redigera användare' : 'Ny användare' }}
        </h2>
        <div class="page-subtitle">
            {{ $user->exists ? 'Uppdatera användaren.' : 'Skapa ny användare.' }}
        </div>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.users.index', absolute: false) }}" class="btn btn-outline-secondary">
            Tillbaka
        </a>
    </div>
</div>

<form method="POST" action="{{ $user->exists ? route('admin.users.update', $user, false) : route('admin.users.store', absolute: false) }}">
    @csrf
    @if($user->exists)
        @method('PUT')
    @endif

    <div class="form-layout">
        <div class="page-card">
            <div class="section-title">Grunduppgifter</div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Namn</label>
                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="{{ old('name', $user->name) }}"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">E-post</label>
                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        value="{{ old('email', $user->email) }}"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Telefon</label>
                    <input
                        type="text"
                        name="phone"
                        class="form-control"
                        value="{{ old('phone', $user->phone) }}"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Aktiv</label>
                    <select name="is_active" class="form-select">
                        <option value="1" @selected((string) old('is_active', $user->is_active ?? 1) === '1')>Ja</option>
                        <option value="0" @selected((string) old('is_active', $user->is_active ?? 1) === '0')>Nej</option>
                    </select>
                </div>
            </div>

            <div class="section-title mt-4">Roller</div>

            <div class="role-grid">
                @forelse($roles as $role)
                    <label class="role-card">
                        <input
                            type="checkbox"
                            name="roles[]"
                            value="{{ $role->slug }}"
                            class="form-check-input"
                            @checked(in_array((string) $role->slug, $selectedRoles, true))
                        >
                        <div>
                            <div class="fw-semibold">{{ $role->name }}</div>
                            @if(!empty($role->description))
                                <div class="small-muted">{{ $role->description }}</div>
                            @endif
                        </div>
                    </label>
                @empty
                    <div class="small-muted">Inga roller hittades.</div>
                @endforelse
            </div>

            @error('roles')
                <div class="text-danger mt-2">{{ $message }}</div>
            @enderror

            @error('roles.*')
                <div class="text-danger mt-2">{{ $message }}</div>
            @enderror

           

            <div class="section-title mt-4">Lösenord</div>

            <div class="row g-3">
                <div class="col-md-6">
                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="{{ $user->exists ? 'Nytt lösenord (valfritt)' : 'Lösenord' }}"
                    >
                </div>

                <div class="col-md-6">
                    <input
                        type="password"
                        name="password_confirmation"
                        class="form-control"
                        placeholder="Bekräfta"
                    >
                </div>
            </div>
        </div>

        <div class="form-side-box">
            <div class="info-label">Spara</div>
            <div class="small-muted mb-3">
                Kontrollera uppgifter, roller och kioskval innan du sparar.
            </div>

            <button class="btn btn-primary w-100">
                Spara användare
            </button>
        </div>
    </div>
</form>

<style>
.role-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.85rem;
    margin-top: 0.75rem;
}

.role-card {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
    background: #f8fafc;
    border: 1px solid var(--brand-line-soft);
    border-radius: 14px;
    padding: 0.95rem 1rem;
    cursor: pointer;
}

.role-card .form-check-input {
    margin-top: 0.2rem;
    flex: 0 0 auto;
}

@media (max-width: 800px) {
    .role-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const kioskCheckbox = document.getElementById('is_kiosk');
    const kioskTargetWrapper = document.getElementById('kiosk_target_wrapper');

    function updateKioskVisibility() {
        if (!kioskCheckbox || !kioskTargetWrapper) {
            return;
        }

        kioskTargetWrapper.style.display = kioskCheckbox.checked ? '' : 'none';
    }

    if (kioskCheckbox) {
        kioskCheckbox.addEventListener('change', updateKioskVisibility);
        updateKioskVisibility();
    }
});
</script>
@endsection