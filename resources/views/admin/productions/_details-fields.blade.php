@php
    $selectedSites = collect(old('sites', isset($production) ? $production->siteKeys() : []));
    $fieldId = $fieldId ?? 'production';
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="{{ $fieldId }}_name">Namn</label>
        <input
            id="{{ $fieldId }}_name"
            type="text"
            name="name"
            class="form-control"
            value="{{ old('name', $production->name ?? 'TV-produktion') }}"
            required
        >
    </div>
    <div class="col-md-3">
        <label class="form-label" for="{{ $fieldId }}_starts_on">Från</label>
        <input
            id="{{ $fieldId }}_starts_on"
            type="date"
            name="starts_on"
            class="form-control"
            value="{{ old('starts_on', isset($production) ? $production->starts_on->format('Y-m-d') : now()->toDateString()) }}"
            required
        >
    </div>
    <div class="col-md-3">
        <label class="form-label" for="{{ $fieldId }}_ends_on">Till</label>
        <input
            id="{{ $fieldId }}_ends_on"
            type="date"
            name="ends_on"
            class="form-control"
            value="{{ old('ends_on', isset($production) ? $production->ends_on->format('Y-m-d') : now()->addDays(7)->toDateString()) }}"
            required
        >
    </div>

    <div class="col-12">
        <div class="form-label mb-1">Anläggningar</div>
        <div class="small-muted mb-2">Var produktionen är. Syns för produktionsteamet på /berget.</div>
        <div class="d-flex flex-wrap gap-3">
            @foreach($siteLabels as $key => $label)
                <div class="form-check">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="sites[]"
                        value="{{ $key }}"
                        id="{{ $fieldId }}_site_{{ $key }}"
                        @checked($selectedSites->contains($key))
                    >
                    <label class="form-check-label" for="{{ $fieldId }}_site_{{ $key }}">{{ $label }}</label>
                </div>
            @endforeach
        </div>
        @error('sites')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <div class="section-title mb-1">Intern information</div>
        <div class="small-muted">Bara för Hemsö. Visas inte för produktionsteamet.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $fieldId }}_company">Bolag</label>
        <input
            id="{{ $fieldId }}_company"
            type="text"
            name="company"
            class="form-control"
            value="{{ old('company', $production->company ?? '') }}"
        >
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $fieldId }}_client">Beställare</label>
        <input
            id="{{ $fieldId }}_client"
            type="text"
            name="client"
            class="form-control"
            value="{{ old('client', $production->client ?? '') }}"
        >
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $fieldId }}_client_contact">Kontaktperson</label>
        <input
            id="{{ $fieldId }}_client_contact"
            type="text"
            name="client_contact"
            class="form-control"
            value="{{ old('client_contact', $production->client_contact ?? '') }}"
        >
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $fieldId }}_client_phone">Telefon</label>
        <input
            id="{{ $fieldId }}_client_phone"
            type="text"
            name="client_phone"
            class="form-control"
            value="{{ old('client_phone', $production->client_phone ?? '') }}"
        >
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $fieldId }}_client_email">E-post</label>
        <input
            id="{{ $fieldId }}_client_email"
            type="email"
            name="client_email"
            class="form-control"
            value="{{ old('client_email', $production->client_email ?? '') }}"
        >
    </div>
    <div class="col-12">
        <label class="form-label" for="{{ $fieldId }}_notes">Intern anteckning</label>
        <textarea
            id="{{ $fieldId }}_notes"
            name="notes"
            class="form-control"
            rows="3"
        >{{ old('notes', $production->notes ?? '') }}</textarea>
    </div>
</div>
