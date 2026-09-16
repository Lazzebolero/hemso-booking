@extends('layouts.berg')

@section('title', 'Närvaro i berget')
@section('heading', 'I berget')

@section('content')
    <div id="berg-live">
        @include('berg.partials.live')
    </div>
@endsection
