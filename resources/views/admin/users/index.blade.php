@extends('layouts.app')
@section('content')
<h1>Användare</h1>
<p><a class="btn btn-primary" href="{{ route('admin.users.create') }}">Ny användare</a></p>
<div class="card"><div class="card-body"><table><thead><tr><th>Namn</th><th>E-post</th><th>Roll</th><th>Aktiv</th><th></th></tr></thead><tbody>
@foreach($users as $user)
<tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->role }}</td><td>{{ $user->is_active ? 'Ja' : 'Nej' }}</td><td><a class="btn" href="{{ route('admin.users.edit',$user) }}">Redigera</a></td></tr>
@endforeach
</tbody></table>{{ $users->links() }}</div></div>
@endsection
