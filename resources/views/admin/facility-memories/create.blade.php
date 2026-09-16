@extends('layouts.app')

@section('content')
@php
    $fp = $routePrefix ?? 'admin';
@endphp

<div class="staff-page-stack">
    @include('partials.ui.flash-messages')

    <x-ui.page-header
        title="Spara minne"
        subtitle="Dokumentera en berättelse med anknytning till anläggningen — text eller ljud."
        icon="bi-journal-text"
    >
        <x-slot:actions>
            <a href="{{ route($fp . '.facility-memories.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Till arkivet
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    @include('partials.facility-memories.form', [
        'formAction' => route($fp . '.facility-memories.store'),
        'cancelUrl' => route($fp . '.facility-memories.index'),
        'tour' => $tour,
        'locations' => $locations,
        'tourOptions' => $tourOptions,
        'cardClass' => 'page-card mb-3',
        'showTourPicker' => true,
        'allowAudioFileUpload' => true,
    ])
</div>
@endsection
