@extends('layouts.guide')

@section('content')
<div class="staff-page-stack">
    @include('partials.ui.flash-messages', ['guide' => true])

    <x-ui.page-header
        :guide="true"
        title="Besökshund"
        subtitle="Registrera hund som lämnas i vård vid grindstugan under guidad tur."
        icon="bi-heart-pulse"
    >
        <x-slot:actions>
            @if(Route::has('visitor-dogs.index'))
                <a href="{{ route('visitor-dogs.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-list-ul me-1"></i>Mina hundar
                </a>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="guide-card">
        @include('visitor-dogs._form', ['defaultVisitDate' => $defaultVisitDate])
    </div>
</div>
@endsection
