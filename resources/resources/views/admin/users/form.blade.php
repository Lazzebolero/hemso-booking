@extends('layouts.app')
@section('content')
<h1>{{ $user->exists ? 'Redigera användare' : 'Ny användare' }}</h1>
<form method="POST" action="{{ $user->exists ? route('admin.users.update',$user) : route('admin.users.store') }}">@csrf @if($user->exists) @method('PUT') @endif
<div class="grid grid-3">
<div><label>Namn</label><input name="name" value="{{ old('name',$user->name) }}"></div>
<div><label>E-post</label><input name="email" value="{{ old('email',$user->email) }}"></div>
<div><label>Telefon</label><input name="phone" value="{{ old('phone',$user->phone) }}"></div>
<div><label>Roll</label><select name="role">@foreach(['admin','host','guide'] as $r)<option value="{{ $r }}" @selected(old('role',$user->role ?? 'guide')==$r)>{{ $r }}</option>@endforeach</select></div>
<div><label>Aktiv</label><select name="is_active"><option value="1" @selected(old('is_active',$user->is_active ?? true)==1)>Ja</option><option value="0" @selected(old('is_active',$user->is_active ?? true)==0)>Nej</option></select></div>
<div><label>Lösenord</label><input type="password" name="password"></div>
<div><label>Bekräfta lösenord</label><input type="password" name="password_confirmation"></div>
</div><p><button class="btn btn-primary">Spara</button></p></form>
@endsection
