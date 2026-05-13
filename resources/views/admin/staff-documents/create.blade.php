@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Nytt personaldokument</h2>
        <div class="page-subtitle">Ladda upp ett dokument för personal, roll eller funktion.</div>
    </div>
</div>

<form method="POST" action="{{ route('admin.staff-documents.store') }}" enctype="multipart/form-data">
    @csrf
    @include('admin.staff-documents._form')
</form>
@endsection