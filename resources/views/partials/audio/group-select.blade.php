<div class="col-md-6">
    <label class="form-label" for="audio_group_id">Grupp / zon</label>
    <select name="audio_group_id" id="audio_group_id" class="form-select">
        <option value="">— Ingen grupp —</option>
        @foreach($groups as $group)
            <option value="{{ $group->id }}" @selected((string) $selectedGroupId === (string) $group->id)>
                {{ $group->name }}
            </option>
        @endforeach
    </select>
    <div class="form-text">Välj vilken del av anläggningen enheten tillhör.</div>
</div>
