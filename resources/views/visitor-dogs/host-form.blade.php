@extends('layouts.app')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
        <h2 class="page-title">Besökshund</h2>
        <div class="page-subtitle">
            Registrera hund som lämnas i vård vid grindstugan under guidad tur.
        </div>
    </div>
    @if(Route::has('visitor-dogs.index'))
        <a href="{{ route('visitor-dogs.index') }}" class="btn btn-outline-secondary">Mina hundar</a>
    @endif
</div>

<div class="page-card">
    @include('visitor-dogs._form', ['defaultVisitDate' => $defaultVisitDate])
</div>
@endsection
