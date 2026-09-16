@php
    use App\Support\AudioChannelSides;

    $selectedChannels = old('channels', [AudioChannelSides::LEFT, AudioChannelSides::RIGHT]);
@endphp

<div class="col-12">
    <label class="form-label d-block">Kanaler</label>
    <div class="btn-group" role="group" aria-label="Kanaler">
        @foreach(AudioChannelSides::options() as $side => $label)
            <input type="checkbox"
                   class="btn-check"
                   name="channels[]"
                   value="{{ $side }}"
                   id="channel-{{ $side }}"
                   @checked(in_array($side, $selectedChannels, true))>
            <label class="btn btn-outline-primary" for="channel-{{ $side }}">{{ $label }}</label>
        @endforeach
    </div>
    <div class="form-text">Välj vilka kanaler enheten ska ha. Varje kanal kan spela olika ljud.</div>
    @error('channels')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>
