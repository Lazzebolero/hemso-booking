@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Ljud</h2>
        <div class="page-subtitle">Styr ljud i olika delar av anläggningen.</div>
    </div>

    <div class="page-actions d-flex flex-wrap gap-2">
        <a href="{{ route('admin.audio.groups.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-collection me-1"></i>Hantera grupper
        </a>
        <a href="{{ route('admin.audio.sounds.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-music-note-beamed me-1"></i>Ljudbibliotek
        </a>
        <a href="{{ route('admin.audio.devices.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Ny enhet
        </a>
        <form method="POST" action="{{ route('admin.audio.stop-all') }}" class="d-inline" onsubmit="return confirm('Stoppa uppspelning på alla enheter?')">
            @csrf
            <button type="submit" class="btn btn-outline-danger">
                <i class="bi bi-stop-circle me-1"></i>Stoppa alla
            </button>
        </form>
    </div>
</div>

@if($groups->isNotEmpty())
    <div class="page-card compact-card">
        <div class="section-title">Zoner / grupper</div>

        <div class="row g-3">
            @foreach($groups as $group)
                @php
                    $deviceCount = $group->devices->count();
                    $playingCount = $group->devices->sum(fn ($device) => $device->loudspeakers->where('status', true)->count());
                @endphp
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <div class="fw-semibold">{{ $group->name }}</div>
                                @if($group->description)
                                    <div class="small-muted">{{ $group->description }}</div>
                                @endif
                            </div>
                            @if(! $group->is_active)
                                <span class="badge-soft">Inaktiv</span>
                            @elseif($playingCount > 0)
                                <span class="badge bg-success">Spelar</span>
                            @endif
                        </div>

                        <div class="small-muted mb-3">{{ $deviceCount }} enheter · {{ $playingCount }} aktiva kanaler</div>

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('admin.audio.groups.show', $group) }}" class="btn btn-sm btn-primary">Styr grupp</a>
                            <form method="POST" action="{{ route('admin.audio.groups.play', $group) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success">Spela grupp</button>
                            </form>
                            <form method="POST" action="{{ route('admin.audio.groups.stop', $group) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger">Stopp</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="page-card compact-card mt-4">
    <div class="section-title">Alla enheter</div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Namn</th>
                    <th>Grupp</th>
                    <th>Plats</th>
                    <th>Kanaler</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($devices as $device)
                    @php
                        $playingCount = $device->loudspeakers->where('status', true)->count();
                    @endphp
                    <tr>
                        <td><span class="fw-semibold">#{{ $device->id }}</span></td>
                        <td>
                            <div class="fw-semibold">{{ $device->name }}</div>
                            @if($device->hostname)
                                <div class="small-muted">{{ $device->hostname }}</div>
                            @endif
                        </td>
                        <td>{{ $device->group?->name ?? '—' }}</td>
                        <td>{{ $device->location ?? '—' }}</td>
                        <td>{{ $device->loudspeakers->count() }}</td>
                        <td>
                            @if(! $device->is_active)
                                <span class="badge-soft">Inaktiv</span>
                            @elseif($playingCount > 0)
                                <span class="badge bg-success">Spelar ({{ $playingCount }})</span>
                            @else
                                <span class="badge-soft">Vilande</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            <a href="{{ route('admin.audio.devices.show', $device) }}" class="btn btn-sm btn-primary">Styr</a>
                            <a href="{{ route('admin.audio.devices.setup', $device) }}" class="btn btn-sm btn-outline-secondary">Pi-setup</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Inga enheter registrerade ännu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($ungroupedDevices->isNotEmpty())
    <div class="alert alert-warning mt-4">
        {{ $ungroupedDevices->count() }} enhet(er) saknar grupp.
        <a href="{{ route('admin.audio.groups.index') }}">Skapa grupper och tilldela enheter</a> för att styra delar av anläggningen.
    </div>
@endif
@endsection
