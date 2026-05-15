@extends($useGuideLayout ? 'layouts.guide' : 'layouts.app')

@section('content')
    @if($useGuideLayout)
        @include('partials.time.edit-guide', ['entry' => $entry])
    @else
        @include('partials.time.edit-app', ['entry' => $entry])
    @endif
@endsection