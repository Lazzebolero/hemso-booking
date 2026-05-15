@extends('layouts.guide')

@section('content')
<div class="guide-card mb-3">
    <div class="page-header mb-0 pb-3 d-flex flex-wrap justify-content-between align-items-start gap-2" style="border-bottom: 1px solid var(--brand-line-soft);">
        <div>
            <h2 class="page-title" style="font-size: 1.15rem;">Besökshund</h2>
            <p class="page-subtitle mb-0" style="font-size: 0.88rem;">
                Registrera hund som lämnas i vård vid grindstugan under guidad tur.
            </p>
        </div>
        @if(Route::has('visitor-dogs.index'))
            <a href="{{ route('visitor-dogs.index') }}" class="btn btn-outline-secondary btn-sm">Mina hundar</a>
        @endif
    </div>
</div>

<div class="guide-card">
    @include('visitor-dogs._form', ['defaultVisitDate' => $defaultVisitDate])
</div>
@endsection
