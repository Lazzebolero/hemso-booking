@php
    $fieldId = $fieldId ?? 'production';
@endphp

<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="section-title mb-1">Produktionsadmin</div>
        <div class="small-muted">
            Personen loggar in på <strong>/berget</strong> och lägger till personal och deltagare där.
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $fieldId }}_admin_name">Namn</label>
        <input
            id="{{ $fieldId }}_admin_name"
            type="text"
            name="admin_name"
            class="form-control @error('admin_name') is-invalid @enderror"
            value="{{ old('admin_name') }}"
            required
        >
        @error('admin_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $fieldId }}_admin_email">E-post</label>
        <input
            id="{{ $fieldId }}_admin_email"
            type="email"
            name="admin_email"
            class="form-control @error('admin_email') is-invalid @enderror"
            value="{{ old('admin_email') }}"
            autocomplete="off"
            required
        >
        @error('admin_email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $fieldId }}_admin_password">Lösenord</label>
        <input
            id="{{ $fieldId }}_admin_password"
            type="text"
            name="admin_password"
            class="form-control @error('admin_password') is-invalid @enderror"
            value="{{ old('admin_password') }}"
            minlength="8"
            autocomplete="new-password"
            required
        >
        @error('admin_password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Minst 8 tecken. Visa det för produktionsadmin.</div>
    </div>
</div>
