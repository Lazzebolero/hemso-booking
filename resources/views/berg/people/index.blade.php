@extends('layouts.berg')

@section('title', 'Personer i berget')
@section('heading', 'Personer')

@section('content')
    <form method="POST" action="{{ url('/berget/personer') }}">
        @csrf
        <h2 class="berg-h">Ny person</h2>

        <div class="berg-field">
            <label for="kind">Nivå</label>
            <select name="kind" id="kind" required>
                @foreach($kindLabels as $kind => $label)
                    <option value="{{ $kind }}" @selected(old('kind', 'staff') === $kind)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="berg-field">
            <label for="name">Namn</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required>
        </div>

        <div class="berg-field" data-login-field>
            <label for="email">E-post</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" autocomplete="username">
        </div>

        <div class="berg-field" data-login-field>
            <label for="password">Lösenord</label>
            <input type="text" name="password" id="password" minlength="8" autocomplete="new-password">
        </div>

        <p class="berg-meta" style="margin-top:0;">
            Deltagare loggar inte in. Stämpla dem som grupp på Närvaro, eller en och en i listan.
            Admin och personal behöver e-post och lösenord.
        </p>
        <button class="berg-submit" type="submit">Lägg till</button>
    </form>

    <div class="berg-section">
        <h2 class="berg-h">Alla ({{ $people->count() }})</h2>
        @forelse($people as $person)
            <div class="berg-row">
                <div>
                    <div class="berg-name">{{ $person->name }}</div>
                    <div class="berg-meta">
                        {{ $person->kindLabel() }}
                        @if($person->hasDeparted())
                            · åkt ut
                        @elseif($person->is_inside)
                            · inne
                        @else
                            · ute
                        @endif
                        @if($person->user?->email)
                            · {{ $person->user->email }}
                        @endif
                    </div>
                </div>
                <div class="berg-actions">
                    <a class="mini-btn mini-edit" href="{{ url('/berget/personer/'.$person->id.'/redigera') }}">Ändra</a>
                    @if((int) $person->user_id !== (int) auth()->id())
                        <form method="POST" action="{{ url('/berget/personer/'.$person->id) }}" onsubmit="return confirm('Ta bort {{ $person->name }}? Inloggningen tas bort så ni kan skapa personen igen.');">
                            @csrf
                            @method('DELETE')
                            <button class="mini-btn mini-delete" type="submit">Ta bort</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="berg-meta">Inga personer ännu.</div>
        @endforelse
    </div>

    <div class="berg-section">
        <h2 class="berg-h">Importera CSV</h2>
        <form method="POST" action="{{ url('/berget/personer/import') }}">
            @csrf
            <div class="berg-field">
                <label for="csv">nivå,namn,epost,lösenord</label>
                <textarea name="csv" id="csv" rows="5" placeholder="personal,Anna Andersson,anna@tv.se,Hemligt12">{{ old('csv') }}</textarea>
            </div>
            <button class="berg-submit" type="submit">Importera</button>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        const kindSelect = document.getElementById('kind');
        const loginFields = document.querySelectorAll('[data-login-field]');
        function toggleLoginFields() {
            const needsLogin = kindSelect.value !== 'participant';
            loginFields.forEach((field) => {
                field.style.display = needsLogin ? '' : 'none';
                field.querySelectorAll('input').forEach((input) => {
                    input.required = needsLogin && input.name !== 'password' ? false : false;
                });
            });
        }
        kindSelect.addEventListener('change', toggleLoginFields);
        toggleLoginFields();
    </script>
@endsection
