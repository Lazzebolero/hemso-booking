<div class="berg-row">
    <div>
        <div class="berg-name">{{ $person->name }}</div>
        <div class="berg-meta">{{ $person->kindLabel() }}{{ $person->isInside() ? ' · inne' : ' · ute' }}</div>
    </div>
    <div class="berg-actions">
        @if($person->isInside())
            <form method="POST" action="{{ url('/berget/personer/'.$person->id.'/stampa') }}">
                @csrf
                <input type="hidden" name="direction" value="out">
                <button class="mini-btn mini-out" type="submit" style="background:#b45309;color:#fff;">Ut</button>
            </form>
        @else
            <form method="POST" action="{{ url('/berget/personer/'.$person->id.'/stampa') }}">
                @csrf
                <input type="hidden" name="direction" value="in">
                <button class="mini-btn mini-in" type="submit" style="background:#16a34a;color:#fff;">In</button>
            </form>
        @endif

        @if($showLeave && $person->isParticipant())
            <form method="POST" action="{{ url('/berget/personer/'.$person->id.'/utrest') }}" onsubmit="return confirm('Märk {{ $person->name }} som utrest? Personen tas inte med när gruppen stämplas.');">
                @csrf
                <button class="mini-btn mini-leave" type="submit" style="background:#dc2626;color:#fff;">Åkt ut</button>
            </form>
        @endif
    </div>
</div>
