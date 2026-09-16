@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Ljudbibliotek</h2>
        <div class="page-subtitle">Ladda upp ljudfiler som Pi-enheterna kan spela upp via URL.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.audio.index') }}" class="btn btn-outline-secondary">Till ljud</a>
    </div>
</div>

<div class="page-card compact-card">
    <div class="section-title">Ladda upp ljud</div>

    <form method="POST" action="{{ route('admin.audio.sounds.store') }}" enctype="multipart/form-data" class="row g-3 align-items-end">
        @csrf
        <div class="col-md-4">
            <label class="form-label" for="name">Namn</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
        </div>
        <div class="col-md-5">
            <label class="form-label" for="audio_file">Ljudfil</label>
            <input type="file" name="audio_file" id="audio_file" class="form-control" accept="audio/*" required>
            <div class="form-text">MP3, WAV, OGG, FLAC. Max 50 MB.</div>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100">Ladda upp</button>
        </div>
    </form>
</div>

<div class="page-card compact-card mt-4">
    <div class="section-title">Uppladdade ljud</div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Namn</th>
                    <th>Fil</th>
                    <th>URL (path)</th>
                    <th>Kanaler</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($sounds as $sound)
                    <tr>
                        <td class="fw-semibold">{{ $sound->name }}</td>
                        <td>{{ $sound->original_name }}</td>
                        <td><code class="small">{{ $sound->path }}</code></td>
                        <td>{{ $sound->loudspeakers_count }}</td>
                        <td class="text-nowrap">
                            <a href="{{ $sound->path }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">Öppna</a>
                            <form method="POST" action="{{ route('admin.audio.sounds.destroy', $sound) }}" class="d-inline" onsubmit="return confirm('Ta bort ljudfilen?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" @disabled($sound->loudspeakers_count > 0)>Ta bort</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Inga ljud uppladdade ännu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
