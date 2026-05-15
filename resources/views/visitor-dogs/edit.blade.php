@extends($useGuideLayout ? 'layouts.guide' : 'layouts.app')

@section('content')
<div class="page-header mb-3">
    <div>
        <h2 class="page-title" @if($useGuideLayout) style="font-size: 1.15rem;" @endif>Redigera {{ $dog->dog_name }}</h2>
        <p class="page-subtitle mb-0" @if($useGuideLayout) style="font-size: 0.88rem;" @endif>Uppdatera uppgifter eller byt bild.</p>
    </div>
</div>

@if($useGuideLayout)
    <div class="guide-card">
@else
    <div class="page-card" style="max-width: 32rem;">
@endif
    @include('visitor-dogs._form', [
        'dog' => $dog,
        'formAction' => route('visitor-dogs.update', $dog),
        'cancelUrl' => route('visitor-dogs.show', $dog),
        'photoUrl' => $dog->photo_path ? route('visitor-dogs.photo', $dog) : null,
    ])
</div>
@endsection
