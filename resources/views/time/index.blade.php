@extends($useGuideLayout ? 'layouts.guide' : 'layouts.app')

@section('content')
    <x-ui.page-header
        :guide="$useGuideLayout"
        title="Tidrapportering"
        subtitle="Stämpla tid, kontrollera pass och skicka in när allt stämmer."
        icon="bi-clock-history"
    >
        <x-slot:actions>
            @include('partials.time.clock-actions', [
                'openEntry' => $openEntry,
                'guide' => $useGuideLayout,
            ])
        </x-slot>
    </x-ui.page-header>

    @include('partials.ui.flash-messages', ['guide' => $useGuideLayout])

    @include('partials.time.open-entries-alert', [
        'openEntries' => $openEntries,
        'openEntry' => $openEntry,
        'guide' => $useGuideLayout,
    ])

    @include('partials.time.period-summary', [
        'from' => $from,
        'to' => $to,
        'totalFormatted' => $totalFormatted,
        'filter' => $filter,
        'guide' => $useGuideLayout,
    ])

    @if($useGuideLayout)
        @include('partials.time.entries-guide', ['entries' => $entries])
    @else
        @include('partials.time.entries-app', ['entries' => $entries])
    @endif
@endsection
