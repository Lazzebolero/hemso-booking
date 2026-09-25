@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">{{ $production->name }}</h2>
        <div class="page-subtitle">
            {{ $production->starts_on->format('Y-m-d') }} – {{ $production->ends_on->format('Y-m-d') }}
            @if($production->siteListLabel() !== '')
                · {{ $production->siteListLabel() }}
            @endif
            · {{ $insideCount }} inne i berget just nu
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.productions.presence') }}" class="btn btn-outline-secondary">Närvaro i berget</a>
        <a href="{{ route('admin.productions.log', ['production_id' => $production->id]) }}" class="btn btn-outline-secondary">In/ut-logg</a>
        <a href="{{ route('admin.productions.index') }}" class="btn btn-outline-secondary">Tillbaka</a>
    </div>
</div>

<div class="page-card mb-4">
    <div class="section-title mb-3">Uppgifter</div>
    <form method="POST" action="{{ route('admin.productions.update', $production) }}">
        @csrf
        @method('PUT')
        @include('admin.productions._details-fields', ['production' => $production, 'fieldId' => 'edit_production'])
        <div class="mt-3">
            <button class="btn btn-primary" type="submit">Spara ändringar</button>
        </div>
    </form>
</div>

<div class="page-card mb-4">
    <div class="section-title mb-2">Viktiga nummer</div>
    <p class="small-muted mb-3">
        Syns för produktionsteamet på <strong>/berget</strong>. De kan trycka för att ringa.
    </p>

    @forelse($production->phoneNumbers as $number)
        <form method="POST" action="{{ route('admin.productions.phone-numbers.update', [$production, $number]) }}" class="row g-2 align-items-end mb-2">
            @csrf
            @method('PUT')
            <div class="col-md-5">
                <label class="form-label" for="phone_label_{{ $number->id }}">Vad</label>
                <input
                    id="phone_label_{{ $number->id }}"
                    type="text"
                    name="label"
                    class="form-control"
                    value="{{ $number->label }}"
                    required
                >
            </div>
            <div class="col-md-4">
                <label class="form-label" for="phone_value_{{ $number->id }}">Nummer</label>
                <input
                    id="phone_value_{{ $number->id }}"
                    type="text"
                    name="phone"
                    class="form-control"
                    value="{{ $number->phone }}"
                    required
                >
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-outline-primary flex-grow-1" type="submit">Spara</button>
                <button
                    class="btn btn-outline-danger"
                    type="submit"
                    form="delete-phone-{{ $number->id }}"
                    onclick="return confirm('Ta bort det här numret?')"
                >Ta bort</button>
            </div>
        </form>
        <form id="delete-phone-{{ $number->id }}" method="POST" action="{{ route('admin.productions.phone-numbers.destroy', [$production, $number]) }}" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @empty
        <div class="small-muted mb-3">Inga nummer ännu.</div>
    @endforelse

    <form method="POST" action="{{ route('admin.productions.phone-numbers.store', $production) }}" class="row g-2 align-items-end mt-2">
        @csrf
        <div class="col-md-5">
            <label class="form-label" for="new_phone_label">Nytt: vad</label>
            <input id="new_phone_label" type="text" name="label" class="form-control" value="{{ old('label') }}" placeholder="T.ex. Entré, färja, vakt" required>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="new_phone_value">Nummer</label>
            <input id="new_phone_value" type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="070-123 45 67" required>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary w-100" type="submit">Lägg till</button>
        </div>
    </form>
</div>

<div class="page-card mb-4">
    <div class="section-title mb-2">Produktionsadmin</div>
    <p class="small-muted mb-3">
        Admin loggar in på <strong>/berget</strong> och lägger till personal och deltagare där.
        Lägg till mer än en admin här om det behövs.
    </p>

    <form method="POST" action="{{ route('admin.productions.people.store', $production) }}" class="row g-2 align-items-end">
        @csrf
        <input type="hidden" name="kind" value="admin">
        <div class="col-md-4">
            <label class="form-label" for="add_admin_name">Namn</label>
            <input
                id="add_admin_name"
                type="text"
                name="name"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name') }}"
                required
            >
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label" for="add_admin_email">E-post</label>
            <input
                id="add_admin_email"
                type="email"
                name="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email') }}"
                autocomplete="off"
                required
            >
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label" for="add_admin_password">Lösenord</label>
            <input
                id="add_admin_password"
                type="text"
                name="password"
                class="form-control @error('password') is-invalid @enderror"
                value="{{ old('password') }}"
                minlength="8"
                autocomplete="new-password"
                required
            >
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100" type="submit">Lägg till</button>
        </div>
    </form>
</div>

<div class="page-card">
    <div class="section-title mb-3">Översikt ({{ $people->count() }})</div>
    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Namn</th>
                    <th>Nivå</th>
                    <th>Status</th>
                    <th>Åkt ut</th>
                    <th>Märkt av</th>
                    <th>Inloggning</th>
                </tr>
            </thead>
            <tbody>
                @forelse($people as $person)
                    <tr>
                        <td>{{ $person->name }}</td>
                        <td>{{ $person->kindLabel() }}</td>
                        <td>
                            @if($person->hasDeparted())
                                Åkt ut
                            @elseif($person->is_inside)
                                Inne
                            @else
                                Ute
                            @endif
                        </td>
                        <td>
                            @if($person->hasDeparted())
                                {{ $person->departed_at?->format('Y-m-d H:i') ?? '—' }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if($person->hasDeparted())
                                {{ $latestDepartureByPerson->get($person->id)?->recordedByName() ?? 'Loggades inte' }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $person->user?->email ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted">Inga personer ännu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="page-card mt-4">
    <div class="section-title mb-2">Logg för åkt ut</div>
    <p class="small-muted mb-3">
        Varje gång en deltagare märks som utrest eller återställs, med vem som gjorde det.
        Märkningar som gjordes innan den här loggen fanns har tid. Namnet sparades inte då.
    </p>
    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Tid</th>
                    <th>Person</th>
                    <th>Händelse</th>
                    <th>Av</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departureLogs as $log)
                    <tr>
                        <td>{{ $log->occurred_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $log->personName() }}</td>
                        <td>{{ $log->actionLabel() }}</td>
                        <td>{{ $log->recordedByName() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted">Inga märkningar i loggen ännu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="page-card mt-4">
    <div class="section-title mb-2">In/ut-logg</div>
    <p class="small-muted mb-3">Varje gång någon stämplas in eller ut i berget.</p>
    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Tid</th>
                    <th>Person</th>
                    <th>Händelse</th>
                    <th>Av</th>
                </tr>
            </thead>
            <tbody>
                @forelse($presenceLogs as $log)
                    <tr>
                        <td>{{ $log->occurred_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $log->personName() }}</td>
                        <td>
                            {{ $log->directionLabel() }}
                            @if($log->with_group)
                                <span class="small-muted">· grupp</span>
                            @endif
                        </td>
                        <td>{{ $log->recorder?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted">Inga stämplingar ännu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($presenceLogs->hasPages())
        <div class="mt-3">{{ $presenceLogs->links() }}</div>
    @endif
</div>
@endsection
