@if(!empty($unavailableMessage))
    <div class="berg-status">{{ $unavailableMessage }}</div>
@else
@php
    $actorIsInside = $actor->isInside();
    $insideLabel = $actorIsInside ? 'Du är inne' : 'Du är ute';
    $nextDirection = $actorIsInside ? 'out' : 'in';
    $selfLabel = $actorIsInside ? 'Gå ut ur berget' : 'Gå in i berget';
    $groupCount = (int) ($groupStampCount ?? 0);
    $groupLabel = $actorIsInside
        ? 'Gå ut med deltagarna som är inne'
        : 'Gå in med deltagarna som är ute';
    $selfColor = $actorIsInside ? '#b45309' : '#16a34a';
    $stampBtn = 'display:block;width:100%;min-height:56px;border:0;border-radius:16px;padding:18px 14px;font-size:1.15rem;font-weight:800;margin:0 0 10px;color:#fff;line-height:1.25;-webkit-appearance:none;appearance:none;';
@endphp

<style>
    #berg-live .berg-btn {
        display: block !important;
        width: 100% !important;
        min-height: 56px !important;
        border: 0 !important;
        border-radius: 16px !important;
        padding: 18px 14px !important;
        font-size: 1.15rem !important;
        font-weight: 800 !important;
        margin: 0 0 10px !important;
        color: #fff !important;
        line-height: 1.25 !important;
        -webkit-appearance: none !important;
        appearance: none !important;
    }
</style>

<div class="berg-status {{ $actorIsInside ? 'berg-status-in' : 'berg-status-out' }}">{{ $insideLabel }}</div>

<form method="POST" action="{{ url('/berget/stampa') }}">
    @csrf
    <input type="hidden" name="direction" value="{{ $nextDirection }}">
    <button class="berg-btn {{ $actorIsInside ? 'berg-btn-out' : 'berg-btn-in' }}" type="submit" style="{{ $stampBtn }}background:{{ $selfColor }};">{{ $selfLabel }}</button>
</form>

@if($groupCount > 0)
    <form method="POST" action="{{ url('/berget/stampa') }}">
        @csrf
        <input type="hidden" name="direction" value="{{ $nextDirection }}">
        <input type="hidden" name="with_group" value="1">
        <button class="berg-btn berg-btn-group" type="submit" style="{{ $stampBtn }}background:#1d4ed8;">
            {{ $groupLabel }} ({{ $groupCount }})
        </button>
    </form>
@endif

<div class="berg-section">
    <h2 class="berg-h">Deltagare inne ({{ $participantsInside->count() }})</h2>
    @forelse($participantsInside as $person)
        @include('berg.partials.person-row', ['person' => $person, 'showLeave' => true])
    @empty
        <div class="berg-empty">Inga deltagare inne.</div>
    @endforelse
</div>

<div class="berg-section">
    <h2 class="berg-h">Deltagare ute ({{ $participantsOutside->count() }})</h2>
    @forelse($participantsOutside as $person)
        @include('berg.partials.person-row', ['person' => $person, 'showLeave' => true])
    @empty
        <div class="berg-empty">Inga kvarvarande deltagare ute.</div>
    @endforelse
</div>

<div class="berg-section">
    <h2 class="berg-h">Personal inne ({{ $crewInside->count() }})</h2>
    @forelse($crewInside as $person)
        @include('berg.partials.person-row', ['person' => $person, 'showLeave' => false])
    @empty
        <div class="berg-empty">Ingen personal inne.</div>
    @endforelse
</div>

<div class="berg-section">
    <h2 class="berg-h">Personal ute ({{ $crewOutside->count() }})</h2>
    @forelse($crewOutside as $person)
        @include('berg.partials.person-row', ['person' => $person, 'showLeave' => false])
    @empty
        <div class="berg-empty">Ingen personal ute.</div>
    @endforelse
</div>

@if($departedParticipants->isNotEmpty())
    <div class="berg-section">
        <h2 class="berg-h">Åkt ut ({{ $departedParticipants->count() }})</h2>
        @foreach($departedParticipants as $person)
            <div class="berg-row">
                <div>
                    <div class="berg-name">{{ $person->name }}</div>
                    <div class="berg-meta">Räknas inte i gruppstämpel</div>
                </div>
                @if($canManagePeople)
                    <form method="POST" action="{{ url('/berget/personer/'.$person->id.'/aterstall') }}">
                        @csrf
                        <button class="mini-btn mini-restore" type="submit" style="background:#334155;color:#fff;">Återställ</button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
@endif

@if($canManagePeople)
    <div class="berg-section">
        <a href="{{ url('/berget/personer') }}" class="berg-btn berg-btn-group" style="{{ $stampBtn }}background:#1d4ed8;text-align:center;text-decoration:none;">
            Hantera personer
        </a>
    </div>
@endif
@endif
