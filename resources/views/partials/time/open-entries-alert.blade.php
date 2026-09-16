@props(['openEntries', 'openEntry' => null, 'guide' => false, 'qrStations' => []])

@if($openEntries->isNotEmpty())
    @if($guide)
        <div class="system-message-banner system-message-important mb-4">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <div class="system-message-title">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Du har öppet arbetspass
                    </div>
                    @foreach($openEntries as $open)
                        <div class="system-message-body">
                            Start:
                            <strong>{{ optional($open->clock_in_at_original)->format('Y-m-d H:i') }}</strong>
                            @if(optional($open->clock_in_at_original)->isBefore(now()->startOfDay()))
                                <span class="badge-soft badge-soft-danger ms-2">Från tidigare dag</span>
                            @endif
                        </div>
                    @endforeach
                </div>
                @if($openEntry && count($qrStations) > 0 && Route::has('time.station'))
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($qrStations as $station)
                            <a href="{{ route('time.station', $station['key']) }}" class="btn btn-sm btn-danger">
                                <i class="bi bi-qr-code-scan me-1"></i>Stämpla ut · {{ $station['label'] }}
                            </a>
                        @endforeach
                    </div>
                @elseif($openEntry)
                    <form method="POST" action="{{ route('time.clock-out') }}" data-offline-queue>
                        @csrf
                        <button type="submit" class="btn btn-sm btn-danger">Stämpla ut nu</button>
                    </form>
                @endif
            </div>
        </div>
    @else
        <div class="alert alert-warning border-0 shadow-sm mb-4">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <div class="fw-bold mb-1">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Du har öppet arbetspass
                    </div>
                    @foreach($openEntries as $open)
                        <div class="small">
                            Start:
                            <strong>{{ optional($open->clock_in_at_original)->format('Y-m-d H:i') }}</strong>
                            @if($open->clock_in_at_original && $open->clock_in_at_original->isBefore(now()->startOfDay()))
                                <span class="badge text-bg-danger ms-2">Från tidigare dag</span>
                            @endif
                        </div>
                    @endforeach
                </div>
                @if($openEntry && count($qrStations) > 0 && Route::has('time.station'))
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($qrStations as $station)
                            <a href="{{ route('time.station', $station['key']) }}" class="btn btn-sm btn-danger">
                                <i class="bi bi-qr-code-scan me-1"></i>Stämpla ut · {{ $station['label'] }}
                            </a>
                        @endforeach
                    </div>
                @elseif($openEntry)
                    <form method="POST" action="{{ route('time.clock-out') }}" data-offline-queue>
                        @csrf
                        <button type="submit" class="btn btn-sm btn-danger">Stämpla ut nu</button>
                    </form>
                @endif
            </div>
        </div>
    @endif
@endif
