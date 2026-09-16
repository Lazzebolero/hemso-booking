@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Ny ljudenhet</h2>
        <div class="page-subtitle">Registrera en Raspberry Pi. Enhets-ID måste matcha device_id i Pi-klienten.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.audio.index') }}" class="btn btn-outline-secondary">Tillbaka</a>
    </div>
</div>

<div class="page-card compact-card">
    <form method="POST" action="{{ route('admin.audio.devices.store') }}">
        @csrf

        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label" for="id">Enhets-ID</label>
                <input type="number" name="id" id="id" class="form-control" min="1" max="65535" value="{{ old('id', $device->id) }}" required>
                <div class="form-text">T.ex. 6 för bunkerberry-6.</div>
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
                <input type="text" name="hostname" id="hostname" class="form-control" value="{{ old('hostname', $device->hostname) }}" placeholder="bunkerberry-6">
            </div>

            @include('partials.audio.group-select', ['groups' => $groups, 'selectedGroupId' => old('audio_group_id', $device->audio_group_id)])

            @include('partials.audio.channel-toggle')

            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" @checked(old('is_active', $device->is_active))>
                    <label class="form-check-label" for="is_active">Aktiv</label>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label" for="notes">Anteckningar</label>
                <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes', $device->notes) }}</textarea>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Skapa enhet</button>
        </div>
    </form>
</div>
@endsection
