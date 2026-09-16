@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">{{ $group->name }}</h2>
        <div class="page-subtitle">
            @if($group->description)
                {{ $group->description }} ·
            @endif
            {{ $group->devices->count() }} enheter i gruppen
        </div>
    </div>

    <div class="page-actions d-flex flex-wrap gap-2">
        <a href="{{ route('admin.audio.index') }}" class="btn btn-outline-secondary">Ljud</a>
        <a href="{{ route('admin.audio.groups.index') }}" class="btn btn-outline-secondary">Grupper</a>
        <form method="POST" action="{{ route('admin.audio.groups.play', $group) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success">Spela hela gruppen</button>
        </form>
        <form method="POST" action="{{ route('admin.audio.groups.stop', $group) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-danger">Stoppa gruppen</button>
        </form>
    </div>
</div>

@if($group->devices->isEmpty())
    <div class="alert alert-warning">
        Inga enheter i denna grupp ännu. Tilldela enheter via
        <a href="{{ route('admin.audio.index') }}">enhetslistan</a> (redigera enhet → välj grupp).
    </div>
@else
    @foreach($group->devices as $device)
        <div class="page-card compact-card mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <div class="section-title mb-1">{{ $device->displayLabel() }}</div>
                    <div class="small-muted">{{ $device->location ?? 'Ingen plats angiven' }}</div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.audio.devices.show', $device) }}" class="btn btn-sm btn-outline-secondary">Enhetsdetalj</a>
                    <form method="POST" action="{{ route('admin.audio.devices.stop', $device) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger">Stoppa enhet</button>
                    </form>
                </div>
            </div>

            @if($device->loudspeakers->isEmpty())
                <div class="small-muted">Inga kanaler konfigurerade.</div>
            @else
                <div class="table-responsive-modern">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Kanal</th>
                                <th>Ljud</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($device->loudspeakers as $channel)
                                <tr>
                                        <td>{{ $channel->sideLabel() }}</td>
                                    <td>{{ $channel->sound?->name ?? '—' }}</td>
                                    <td>
                                        @if($channel->status)
                                            <span class="badge bg-success">Spelar</span>
                                        @else
                                            <span class="badge-soft">Stoppad</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        @if($channel->sound_id)
                                            <form method="POST" action="{{ route('admin.audio.channels.play', $channel) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">Spela</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.audio.channels.stop', $channel) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Stopp</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endforeach
@endif
@endsection
