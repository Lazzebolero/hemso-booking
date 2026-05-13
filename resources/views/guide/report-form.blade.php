@extends('layouts.app')
@section('content')
<h1>Ny felrapport</h1>
<form method="POST" action="{{ route('guide.reports.store') }}">@csrf
<div class="grid grid-3">
<div><label>Rubrik</label><input name="title"></div>
<div><label>Kategori</label><select name="category">@foreach(['building','electricity','security','cleaning','equipment','other'] as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach</select></div>
<div><label>Prioritet</label><select name="priority">@foreach(['low','normal','high','urgent'] as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach</select></div>
<div><label>Plats</label><input name="location"></div>
<div style="grid-column:1 / -1"><label>Beskrivning</label><textarea name="description"></textarea></div>
</div><p><button class="btn btn-primary">Skapa</button></p></form>
@endsection
