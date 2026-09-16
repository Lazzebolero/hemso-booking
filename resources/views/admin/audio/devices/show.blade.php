@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">{{ $device->displayLabel() }}</h2>
        <div class="page-subtitle">Styr kanaler och uppspelning för denna Raspberry Pi.</div>
    </div>

    <div class="page-actions d-flex flex-wrap gap-2">
        <a href="{{ route('admin.audio.index') }}" class="btn btn-outline-secondary">Ljud</a>
        <a href="{{ route('admin.audio.devices.setup', $device) }}" class="btn btn-outline-secondary">Pi-setup</a>
        <a href="{{ route('admin.audio.devices.edit', $device) }}" class="btn btn-outline-secondary">Redigera</a>
        <form method="POST" action="{{ route('admin.audio.devices.stop', $device) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-danger">Stoppa alla kanaler</button>
        </form>
    </div>
</div>

<div class="page-card compact-card">
    <div class="section-title">Kanaler</div>

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
                @forelse($device->loudspeakers as $channel)
                    <tr>
                        <td class="fw-semibold">{{ $channel->sideLabel() }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.audio.channels.update', $channel) }}" class="d-flex flex-wrap gap-2 align-items-center">
                                @csrf
                                @method('PATCH')
                                <select name="sound_id" class="form-select form-select-sm" style="min-width: 220px;">
                                    <option value="">— Välj ljud —</option>
                                    @foreach($sounds as $sound)
                                        <option value="{{ $sound->id }}" @selected($channel->sound_id === $sound->id)>{{ $sound->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Spara</button>
                            </form>
                        </td>
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
                            <form method="POST" action="{{ route('admin.audio.channels.destroy', $channel) }}" class="d-inline" onsubmit="return confirm('Ta bort kanalen?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Ta bort</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Inga kanaler ännu. Lägg till vänster och/eller höger nedan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($availableChannels !== [])
    <div class="page-card compact-card mt-4">
        <div class="section-title">Lägg till kanal</div>

        <form method="POST" action="{{ route('admin.audio.devices.channels.store', $device) }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label d-block">Kanal</label>
                <div class="btn-group" role="group">
                    @foreach($availableChannels as $side => $label)
                        <input type="radio"
                               class="btn-check"
                               name="side"
                               value="{{ $side }}"
                               id="add-channel-{{ $side }}"
                               @checked($loop->first)>
                        <label class="btn btn-outline-primary" for="add-channel-{{ $side }}">{{ $label }}</label>
                    @endforeach
                </div>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="sound_id">Ljud (valfritt)</label>
                <select name="sound_id" id="sound_id" class="form-select">
                    <option value="">— Välj ljud —</option>
                    @foreach($sounds as $sound)
                        <option value="{{ $sound->id }}">{{ $sound->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Lägg till</button>
            </div>
        </form>
    </div>
@endif

@if($sounds->isEmpty())
    <div class="alert alert-warning mt-4">
        Inga ljudfiler uppladdade ännu.
        <a href="{{ route('admin.audio.sounds.index') }}">Ladda upp ljud i biblioteket</a> innan du startar uppspelning.
    </div>
@endif
@endsection
