@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Besökshund</h2>
        <div class="page-subtitle">
            Registrera hund som lämnas i vård vid grindstugan under guidad tur.
        </div>
    </div>
</div>

<div class="page-card">
    @include('visitor-dogs._form', ['defaultVisitDate' => $defaultVisitDate])
</div>
@endsection
