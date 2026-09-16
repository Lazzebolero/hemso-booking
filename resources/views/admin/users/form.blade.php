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

    $selectedGuideLanguageIds = collect(
        $selectedGuideLanguageIds ?? old('guide_languages', [])
    )->map(fn ($id) => (string) $id)->all();
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
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
            Tillbaka
        </a>
    </div>
</div>

<form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
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
                            @if($role->slug === \App\Support\Roles::ELEV)
                                <div class="small-muted">Lägg till rollen Guide här när personen ska börja använda appen.</div>
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

            <div id="guide_languages_section" class="mt-4" @unless(in_array(\App\Support\Roles::GUIDE, $selectedRoles, true)) style="display: none;" @endunless>
                <div class="section-title">Guide på språk</div>
                <div class="small-muted mb-3">
                    Markera vilka språk guiden kan hålla turer på. Visas vid guideval och i guideöversikten.
                </div>

                <div class="language-grid">
                    @forelse($languages as $language)
                        <label class="language-card">
                            <input
                                type="checkbox"
                                name="guide_languages[]"
                                value="{{ $language->id }}"
                                class="form-check-input"
                                @checked(in_array((string) $language->id, $selectedGuideLanguageIds, true))
                            >
                            <div>
                                <div class="fw-semibold">{{ $language->name }}</div>
                                <div class="small-muted">{{ strtoupper($language->code) }}</div>
                            </div>
                        </label>
                    @empty
                        <div class="small-muted">Inga aktiva språk hittades. Lägg till under Inställningar.</div>
                    @endforelse
                </div>

                @error('guide_languages')
                    <div class="text-danger mt-2">{{ $message }}</div>
                @enderror

                @error('guide_languages.*')
                    <div class="text-danger mt-2">{{ $message }}</div>
                @enderror
            </div>

            <div class="section-title mt-4">Lösenord</div>

            @if($user->exists)
                <div class="small-muted mb-2">Lämna tomt för att behålla nuvarande lösenord.</div>
            @else
                <div class="small-muted mb-2" id="password_help_default">Krävs för roller som ska logga in.</div>
                <div class="small-muted mb-2" id="password_help_elev" style="display: none;">Trainee/elev behöver inget lösenord – de loggar inte in.</div>
            @endif

            <div class="row g-3" id="password_fields">
                <div class="col-md-6">
                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        autocomplete="new-password"
                        @unless($user->exists) required @endunless
                        placeholder="{{ $user->exists ? 'Nytt lösenord (valfritt)' : 'Lösenord' }}"
                    >
                </div>

                <div class="col-md-6">
                    <input
                        type="password"
                        name="password_confirmation"
                        class="form-control"
                        autocomplete="new-password"
                        placeholder="{{ $user->exists ? 'Bekräfta nytt lösenord' : 'Bekräfta lösenord' }}"
                    >
                </div>
            </div>

            @error('password')
                <div class="text-danger mt-2">{{ $message }}</div>
            @enderror
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

.language-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.85rem;
}

.language-card {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
    background: #f8fafc;
    border: 1px solid var(--brand-line-soft);
    border-radius: 14px;
    padding: 0.85rem 0.95rem;
    cursor: pointer;
}

.language-card .form-check-input {
    margin-top: 0.2rem;
    flex: 0 0 auto;
}

@media (max-width: 800px) {
    .role-grid {
        grid-template-columns: 1fr;
    }

    .language-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const kioskCheckbox = document.getElementById('is_kiosk');
    const kioskTargetWrapper = document.getElementById('kiosk_target_wrapper');
    const guideLanguagesSection = document.getElementById('guide_languages_section');
    const guideRoleCheckbox = document.querySelector('input[name="roles[]"][value="guide"]');
    const elevRoleCheckbox = document.querySelector('input[name="roles[]"][value="elev"]');
    const roleCheckboxes = Array.from(document.querySelectorAll('input[name="roles[]"]'));
    const passwordFields = document.getElementById('password_fields');
    const passwordHelpDefault = document.getElementById('password_help_default');
    const passwordHelpElev = document.getElementById('password_help_elev');
    const passwordInput = document.querySelector('input[name="password"]');
    const passwordConfirmInput = document.querySelector('input[name="password_confirmation"]');
    const isCreateForm = @json(! $user->exists);

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

    function updateGuideLanguagesVisibility() {
        if (!guideLanguagesSection || !guideRoleCheckbox) {
            return;
        }

        guideLanguagesSection.style.display = guideRoleCheckbox.checked ? '' : 'none';
    }

    if (guideRoleCheckbox) {
        guideRoleCheckbox.addEventListener('change', updateGuideLanguagesVisibility);
        updateGuideLanguagesVisibility();
    }

    function selectedRoleSlugs() {
        return roleCheckboxes
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);
    }

    function isScheduleOnlySelection() {
        const selected = selectedRoleSlugs();

        return selected.length > 0 && selected.every(slug => slug === 'elev');
    }

    function updatePasswordRequirements() {
        if (!isCreateForm || !passwordFields) {
            return;
        }

        const scheduleOnly = isScheduleOnlySelection();

        if (passwordHelpDefault) {
            passwordHelpDefault.style.display = scheduleOnly ? 'none' : '';
        }

        if (passwordHelpElev) {
            passwordHelpElev.style.display = scheduleOnly ? '' : 'none';
        }

        if (passwordInput) {
            passwordInput.required = !scheduleOnly;
            passwordInput.disabled = scheduleOnly;
            if (scheduleOnly) {
                passwordInput.value = '';
            }
        }

        if (passwordConfirmInput) {
            passwordConfirmInput.required = !scheduleOnly;
            passwordConfirmInput.disabled = scheduleOnly;
            if (scheduleOnly) {
                passwordConfirmInput.value = '';
            }
        }
    }

    roleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updatePasswordRequirements);
    });

    updatePasswordRequirements();
});
</script>
@endsection