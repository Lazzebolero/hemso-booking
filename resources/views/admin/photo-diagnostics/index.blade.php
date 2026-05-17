@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Bilddiagnostik</h2>
        <div class="page-subtitle">
            Read-only kontroll av filer som påverkar hundbilder, felrapportbilder, PWA och uppladdningsflöden.
        </div>
    </div>
</div>

<div class="page-card mb-4">
    <div class="section-title">OPcache</div>
    <dl class="row mb-0">
        <dt class="col-sm-3">Tillgänglig</dt>
        <dd class="col-sm-9">{{ $opcache['available'] ? 'Ja' : 'Nej' }}</dd>

        <dt class="col-sm-3">Aktiverad</dt>
        <dd class="col-sm-9">{{ $opcache['enabled'] === null ? 'Okänt' : ($opcache['enabled'] ? 'Ja' : 'Nej') }}</dd>

        <dt class="col-sm-3">Validate timestamps</dt>
        <dd class="col-sm-9">{{ $opcache['validate_timestamps'] === null ? 'Okänt' : ($opcache['validate_timestamps'] ? 'Ja' : 'Nej') }}</dd>

        <dt class="col-sm-3">Revalidate freq</dt>
        <dd class="col-sm-9">{{ $opcache['revalidate_freq'] ?? 'Okänt' }}</dd>
    </dl>
</div>

<div class="page-card mb-4">
    <div class="section-title">Filhashar</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Fil</th>
                    <th>Status</th>
                    <th>OPcache</th>
                    <th>Ändrad</th>
                    <th>SHA1 på servern</th>
                    <th>Förväntad SHA1</th>
                </tr>
            </thead>
            <tbody>
                @foreach($files as $file)
                    <tr>
                        <td><code>{{ $file['path'] }}</code></td>
                        <td>
                            @if(! $file['exists'])
                                <span class="badge bg-danger">Saknas</span>
                            @elseif($file['matches_expected'])
                                <span class="badge bg-success">Matchar</span>
                            @else
                                <span class="badge bg-warning text-dark">Avviker</span>
                            @endif
                        </td>
                        <td>
                            @if($file['opcache_cached'] === null)
                                Okänt
                            @else
                                {{ $file['opcache_cached'] ? 'Cachad' : 'Ej cachad' }}
                            @endif
                        </td>
                        <td>{{ $file['modified_at'] ?? '-' }}</td>
                        <td><code>{{ $file['actual_sha1'] ?? '-' }}</code></td>
                        <td><code>{{ $file['expected_sha1'] }}</code></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="page-card mb-4">
    <div class="section-title">Hundens bildfält på servern</div>
    <pre class="diagnostics-pre">{{ $dogInput }}</pre>
</div>

<div class="page-card">
    <div class="section-title">Felrapportens bildfält på servern</div>
    <pre class="diagnostics-pre">{{ $reportInput }}</pre>
</div>

<style>
.diagnostics-pre {
    background: #0f172a;
    color: #e2e8f0;
    border-radius: 0.75rem;
    padding: 1rem;
    white-space: pre-wrap;
}
</style>
@endsection
