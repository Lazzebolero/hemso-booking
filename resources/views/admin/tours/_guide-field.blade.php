@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="{{ $guideFieldClass ?? 'col-md-6' }}">
    <label class="form-label">Huvudguide</label>
    <select
        name="guide_id"
        id="guide_id"
        class="form-select"
        data-availability-url="{{ route($prefix . '.guides.availability') }}"
        data-ignore-tour-id="{{ $tour->id ?? '' }}"
        data-tour-id="{{ $tour->id ?? '' }}"
    >
        <option value="">Ej tilldelad</option>

        @foreach($guides as $guide)
            @php
                $guideShift = $guide->workShifts->first();
                $guideLabel = $guide->name;

                if ($guideShift && !empty($guideShift->start_time)) {
                    $guideLabel .= ' [' . substr($guideShift->start_time, 0, 5) . ']';
                } else {
                    $guideLabel .= ' [Inget pass]';
                }
            @endphp

            <option
                value="{{ $guide->id }}"
                data-shift-start="{{ $guideShift?->start_time ? substr($guideShift->start_time, 0, 5) : '' }}"
                data-has-shift="{{ $guideShift ? '1' : '0' }}"
                data-has-conflict="0"
                data-conflict-text=""
                @selected(old('guide_id', $tour->guide_id ?? '') == $guide->id)
            >
                {{ $guideLabel }}
            </option>
        @endforeach
    </select>

    <div class="form-text">
        Alla guider visas. Schemainfo, turkrockar och språkmatchning uppdateras när datum eller tid ändras.
    </div>

    <div id="guide_schedule_status" class="guide-schedule-status d-none mt-2">
        <span id="guide_schedule_badge" class="guide-schedule-badge"></span>
        <span id="guide_schedule_text" class="guide-schedule-text"></span>
    </div>
</div>
