@extends('layouts.app')

@section('content')
@php
    $hostname = 'bunkerberry-'.$device->id;
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Pi-setup — {{ $device->displayLabel() }}</h2>
        <div class="page-subtitle">Förbered SD-kortet på PC med Raspberry Pi Imager — inget skärm/tangentbord behövs.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.audio.devices.show', $device) }}" class="btn btn-outline-secondary">Tillbaka till styrpanel</a>
    </div>
</div>

<div class="page-card compact-card">
    <div class="section-title">Snabbguide</div>
    <ol class="mb-0">
        <li><strong>Raspberry Pi Imager</strong> — skriv OS, användare <code>{{ $hostname }}</code>, lösenord, wifi, SSH. Klistra in <code>sd-firstrun.sh</code> under <em>Run script at first boot</em>.</li>
        <li><strong>Efter skrivning</strong> — kopiera filer till boot (se nedan) och redigera <code>config.txt</code> (HiFiBerry).</li>
        <li><strong>Starta Pi</strong> — vänta ~5 min, hitta IP, testa i Hemso-admin.</li>
    </ol>
    <p class="small-muted mt-3 mb-0">Fullständig guide: <code>scripts/pi-audio/SD-CARD-SETUP.txt</code> i projektet.</p>
</div>

<div class="page-card compact-card mt-4">
    <div class="section-title">1. Imager — denna enhet</div>
    <div class="table-responsive-modern">
        <table class="table-modern">
            <tbody>
                <tr><th style="width: 200px;">Värdnamn</th><td><code>{{ $hostname }}</code></td></tr>
                <tr><th>Användarnamn</th><td><code>{{ $hostname }}</code></td></tr>
                <tr><th>device_id</th><td><strong>{{ $device->id }}</strong></td></tr>
                <tr><th>Första start-script</th><td><code>scripts/pi-audio/sd-firstrun.sh</code></td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="page-card compact-card mt-4">
    <div class="section-title">2. Kopiera till boot-partitionen (efter Imager)</div>
    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr><th>Från projektet</th><th>Till boot</th></tr>
            </thead>
            <tbody>
                <tr><td><code>scripts/pi-audio/script.py</code></td><td><code>script.py</code></td></tr>
                <tr><td>Nedladdning nedan</td><td><code>bunkerberry.conf</code></td></tr>
                <tr><td><code>{{ $hostname }}</code> (en rad text)</td><td><code>bunkerberry.user</code></td></tr>
                <tr><td><code>scripts/pi-audio/config.txt.snippet</code></td><td>Lägg till i <code>config.txt</code></td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="page-card compact-card mt-4">
    <div class="section-title">3. bunkerberry.conf för detta kort</div>
    <div class="table-responsive-modern mb-3">
        <table class="table-modern">
            <tbody>
                <tr><th style="width: 200px;">Databasvärd</th><td><code>{{ $dbHost }}</code></td></tr>
                <tr><th>Databas</th><td><code>{{ $dbName }}</code></td></tr>
                <tr><th>Användare</th><td><code>{{ $dbUser }}</code></td></tr>
                <tr><th>Lösenord</th><td>Ingår inte på sidan — bara i nedladdad fil</td></tr>
            </tbody>
        </table>
    </div>

    @if ($credentialsConfigured)
        <form method="POST" action="{{ route('admin.audio.devices.setup-config', $device) }}">
            @csrf
            <button type="submit" class="btn btn-primary">Ladda ner bunkerberry.conf</button>
        </form>
        <p class="small-muted mt-2 mb-0">Spara filen som <code>bunkerberry.conf</code> på SD-kortets boot-partition. Lämna den inte i webbläsarens nedladdningsmapp.</p>
    @else
        <div class="alert alert-warning mb-0">
            Sätt <code>AUDIO_FLEET_PI_DB_USERNAME</code> och <code>AUDIO_FLEET_PI_DB_PASSWORD</code> i <code>.env</code>
            (dedikerad read-only MySQL-användare). Appens <code>DB_PASSWORD</code> används inte.
        </div>
    @endif
</div>

<div class="page-card compact-card mt-4">
    <div class="section-title">4. bunkerberry.user</div>
    <pre class="bg-light p-3 rounded small mb-0">{{ $hostname }}</pre>
</div>

<div class="page-card compact-card mt-4">
    <div class="section-title">5. WinSCP efter start (valfritt kontroll)</div>
    <p class="small-muted mb-2">Använd Imager-lösenordet. Kontrollera att tjänsten kör:</p>
    <pre class="bg-light p-3 rounded small mb-0">sudo systemctl status bunkerberry
sudo journalctl -u bunkerberry -f</pre>
</div>
@endsection
