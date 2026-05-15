@foreach($navigationQuery ?? [] as $name => $value)
    @if(is_string($value) && $value !== '')
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endif
@endforeach
