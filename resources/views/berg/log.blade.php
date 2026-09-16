@extends('layouts.berg')

@section('title', 'In/ut-logg')
@section('heading', 'Logg')

@section('content')
    @if(!empty($unavailableMessage))
        <div class="berg-status">{{ $unavailableMessage }}</div>
    @else
        <p class="berg-meta" style="margin-top:0;">Alla in- och utstämplingar, senaste först.</p>

        @php $lastDate = null; @endphp
        @forelse($logs as $log)
            @php $date = $log->occurred_at->toDateString(); @endphp
            @if($date !== $lastDate)
                <h2 class="berg-h" style="{{ $lastDate === null ? 'margin-top:0;' : '' }}">{{ $log->occurred_at->format('Y-m-d') }}</h2>
                @php $lastDate = $date; @endphp
            @endif

            <div class="berg-row">
                <div>
                    <div class="berg-name">{{ $log->personName() }}</div>
                    <div class="berg-meta">
                        {{ $log->occurred_at->format('H:i') }}
                        @if($log->with_group)
                            · grupp
                        @endif
                        @if($log->recordedByName())
                            · av {{ $log->recordedByName() }}
                        @endif
                    </div>
                </div>
                <span class="berg-log-dir {{ $log->isIn() ? 'berg-log-in' : 'berg-log-out' }}">
                    {{ $log->directionLabel() }}
                </span>
            </div>
        @empty
            <div class="berg-meta">Inga in- eller utstämplingar ännu.</div>
        @endforelse
    @endif
@endsection
