@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Redigera {{ $device->displayLabel() }}</h2>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.audio.devices.show', $device) }}" class="btn btn-outline-secondary">Tillbaka</a>
    </div>
</div>

<div class="page-card compact-card">
    <form method="POST" action="{{ route('admin.audio.devices.update', $device) }}">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Enhets-ID</label>
                <input type="text" class="form-control" value="#{{ $device->id }}" disabled>
            </div>

            <div class="col-md-9">
                <label class="form-label" for="name">Namn</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $device->name) }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="location">Plats</label>
                <input type="text" name="location" id="location" class="form-control" value="{{ old('location', $device->location) }}">
            </div>

            <div class="col-md-6">
                <label class="form-label" for="hostname">Värdnamn</label>
                <input type="text" name="hostname" id="hostname" class="form-control" value="{{ old('hostname', $device->hostname) }}">
            </div>

            @include('partials.audio.group-select', ['groups' => $groups, 'selectedGroupId' => old('audio_group_id', $device->audio_group_id)])

            <div class="col-12">
                <label class="form-label" for="notes">Anteckningar</label>
                <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes', $device->notes) }}</textarea>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" @checked(old('is_active', $device->is_active))>
                    <label class="form-check-label" for="is_active">Aktiv</label>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">Spara</button>
        </div>
    </form>

    <hr class="my-4">

    <form method="POST" action="{{ route('admin.audio.devices.destroy', $device) }}" onsubmit="return confirm('Ta bort enheten och alla kanaler?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger">Ta bort enhet</button>
    </form>
</div>
@endsection
