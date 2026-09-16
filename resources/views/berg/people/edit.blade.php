@extends('layouts.berg')

@section('title', 'Ändra person')
@section('heading', 'Ändra person')

@section('content')
    <form method="POST" action="{{ url('/berget/personer/'.$person->id) }}">
        @csrf
        @method('PUT')

        <div class="berg-field">
            <label for="kind">Nivå</label>
            <select name="kind" id="kind" required>
                @foreach($kindLabels as $kind => $label)
                    <option value="{{ $kind }}" @selected(old('kind', $person->kind) === $kind)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="berg-field">
            <label for="name">Namn</label>
            <input type="text" name="name" id="name" value="{{ old('name', $person->name) }}" required>
        </div>

        <div class="berg-field" data-login-field>
            <label for="email">E-post</label>
            <input type="email" name="email" id="email" value="{{ old('email', $person->user?->email) }}" autocomplete="username">
        </div>

        <div class="berg-field" data-login-field>
            <label for="password">Nytt lösenord</label>
            <input type="text" name="password" id="password" minlength="8" autocomplete="new-password" placeholder="Lämna tomt för att behålla">
        </div>

        <p class="berg-meta" style="margin-top:0;">Lösenord krävs bara om personen inte kan logga in ännu.</p>
        <button class="berg-submit" type="submit">Spara</button>
    </form>

    @if((int) $person->user_id !== (int) auth()->id())
        <form method="POST" action="{{ url('/berget/personer/'.$person->id) }}" class="berg-section" onsubmit="return confirm('Ta bort {{ $person->name }}? Inloggningen tas bort så ni kan skapa personen igen.');">
            @csrf
            @method('DELETE')
            <button class="berg-submit" type="submit" style="background:#dc2626;">Ta bort</button>
        </form>
    @else
        <p class="berg-meta berg-section">Du kan inte ta bort ditt eget konto.</p>
    @endif
@endsection

@section('scripts')
    <script>
        const kindSelect = document.getElementById('kind');
        const loginFields = document.querySelectorAll('[data-login-field]');
        function toggleLoginFields() {
            const needsLogin = kindSelect.value !== 'participant';
            loginFields.forEach((field) => {
                field.style.display = needsLogin ? '' : 'none';
            });
        }
        kindSelect.addEventListener('change', toggleLoginFields);
        toggleLoginFields();
    </script>
@endsection
