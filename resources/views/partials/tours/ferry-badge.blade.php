@if($tour->isFerryAdjusted())
    <span class="tour-ferry-badge" title="Starttid korrigerad p.g.a. färja">
        @include('partials.icons.ferry')
        Färjekorrigerad
    </span>
@endif
