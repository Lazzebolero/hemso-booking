@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Redigera personaldokument</h2>
        <div class="page-subtitle">Uppdatera dokumentets målgrupp, text eller fil.</div>
    </div>
</div>

<form method="POST" action="{{ route('admin.staff-documents.update', $document) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.staff-documents._form')
</form>
@endsection