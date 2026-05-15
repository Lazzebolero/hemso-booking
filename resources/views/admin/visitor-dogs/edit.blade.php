@extends('layouts.app')

@section('content')
@php
    $vPrefix = $visitorDogsRoutePrefix ?? 'admin';
@endphp
<div class="page-header">
    <div>
        <h2 class="page-title">Redigera {{ $dog->dog_name }}</h2>
        <div class="page-subtitle">Uppdatera uppgifter eller byt bild.</div>
    </div>
    <div class="page-actions">
        <a href="{{ route($vPrefix . '.visitor-dogs.show', $dog) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Tillbaka
        </a>
    </div>
</div>

<div class="page-card" style="max-width: 32rem;">
    @include('visitor-dogs._form', [
        'dog' => $dog,
        'formAction' => route($vPrefix . '.visitor-dogs.update', $dog),
        'cancelUrl' => route($vPrefix . '.visitor-dogs.show', $dog),
        'photoUrl' => $dog->photo_path ? route($vPrefix . '.visitor-dogs.photo', $dog) : null,
    ])
</div>
@endsection
