@extends('layouts.berg')

@section('title', 'Viktiga nummer')
@section('heading', 'Nummer')

@section('content')
    @if(!empty($unavailableMessage))
        <div class="berg-status">{{ $unavailableMessage }}</div>
    @else
        <p class="berg-meta" style="margin-top:0;">Tryck på ett nummer för att ringa.</p>

        @forelse($phoneNumbers as $number)
            <a class="berg-phone" href="{{ $number->telHref() }}">
                <strong>{{ $number->label }}</strong>
                <span>{{ $number->phone }}</span>
            </a>
        @empty
            <div class="berg-meta">Inga viktiga nummer inlagda ännu.</div>
        @endforelse
    @endif
@endsection
