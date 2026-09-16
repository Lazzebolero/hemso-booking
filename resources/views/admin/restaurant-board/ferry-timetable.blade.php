<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="30">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Färjetidtabell – Hemsöleden</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <style>
        :root {
            --brand-line-soft: #dbe3ee;
            --text-main: #0f172a;
            --text-soft: #64748b;
            --card-bg: #ffffff;
            --accent: #0f766e;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Figtree', sans-serif;
            background: linear-gradient(180deg, #f8fafc 0%, #eef3f9 100%);
            color: var(--text-main);
        }

        .board-shell {
            min-height: 100vh;
            padding: 20px;
        }

        .board-top {
            display: flex;
            justify-content: space-between;
            align-items: start;
            gap: 16px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .board-title {
            margin: 0;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .board-subtitle {
            margin-top: 6px;
            color: var(--text-soft);
            font-size: 1rem;
        }

        .board-updated {
            font-size: 0.95rem;
            color: var(--text-soft);
            font-weight: 700;
            white-space: nowrap;
        }

        .board-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .board-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 6px 12px;
            border-radius: 10px;
            border: 1px solid var(--brand-line-soft);
            background: #fff;
            color: var(--text-soft);
            font-size: 0.85rem;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }

        .board-btn.is-active {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .panel {
            background: var(--card-bg);
            border: 1px solid var(--brand-line-soft);
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
            margin-bottom: 16px;
        }

        .panel-title {
            margin: 0 0 14px;
            font-size: 1.2rem;
            font-weight: 800;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            text-align: left;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-soft);
            padding: 10px 10px;
            border-bottom: 1px solid var(--brand-line-soft);
        }

        tbody td {
            padding: 12px 10px;
            border-bottom: 1px solid #edf2f7;
            vertical-align: middle;
            font-size: 0.98rem;
        }

        tbody tr:last-child td { border-bottom: none; }

        .muted { color: var(--text-soft); }

        .fw-bold { font-weight: 800; }

        .small-muted {
            color: var(--text-soft);
            font-size: 0.88rem;
        }

        .ferry-departure-list {
            display: grid;
            gap: 8px;
        }

        .ferry-departure-item {
            display: grid;
            grid-template-columns: 72px 1fr;
            gap: 12px;
            align-items: center;
            border: 1px solid var(--brand-line-soft);
            border-radius: 14px;
            padding: 10px 12px;
            background: #fff;
        }

        .ferry-departure-item.is-next {
            border-color: rgba(15, 118, 110, 0.35);
            background: rgba(15, 118, 110, 0.06);
        }

        .ferry-departure-item.is-cancelled {
            opacity: 0.72;
        }

        .ferry-departure-time {
            font-size: 1.1rem;
            font-weight: 800;
        }

        .alert {
            border-radius: 12px;
            padding: 10px 12px;
            margin-bottom: 8px;
        }

        .alert-danger { background: #fee2e2; color: #991b1b; }
        .alert-warning { background: #fef3c7; color: #92400e; }
        .alert-info { background: #dbeafe; color: #1e40af; }
    </style>
</head>
<body>
    <div class="board-shell">
        <div class="board-top">
            <div>
                <h1 class="board-title">Färjetidtabell</h1>
                <div class="board-subtitle">
                    Hemsöleden · avgångar från Strinningen
                    · version {{ $timetableRevision ?? '?' }}
                </div>
            </div>

            <div>
                <div class="board-updated">Senast uppdaterad: {{ $nowLabel }}</div>
                <div class="board-actions" style="margin-top: 8px;">
                    <a href="{{ $backUrl }}" class="board-btn">Tillbaka</a>
                </div>
            </div>
        </div>

        @include('partials.ferry.status-card', [
            'snapshot' => $ferrySnapshot ?? [],
            'variant' => 'kiosk',
        ])

        <div class="panel">
            <div class="board-actions" style="margin-bottom: 14px;">
                @foreach(\App\Support\FerryDayTypes::labels() as $type => $label)
                    <a
                        href="{{ request()->url() }}?day_type={{ $type }}"
                        class="board-btn {{ ($dayType ?? '') === $type ? 'is-active' : '' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <h2 class="panel-title">Planerad tidtabell · {{ \App\Support\FerryDayTypes::labels()[$dayType] ?? $dayType }}</h2>

            <table>
                <thead>
                    <tr>
                        <th style="width: 120px;">Tid</th>
                        <th>Kallelsetur</th>
                        <th>Dubbleringsturer</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departures as $departure)
                        <tr>
                            <td class="fw-bold">{{ $departure['time'] }}</td>
                            <td>{{ ($departure['requires_call'] ?? false) ? 'Ja' : 'Nej' }}</td>
                            <td>{{ ($departure['no_duplicates'] ?? false) ? 'Nej' : '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="muted">Inga avgångar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="panel">
            <h2 class="panel-title">Dagens avgångar med live-status</h2>
            <div class="small-muted" style="margin-bottom: 12px;">
                {{ $ferrySnapshot['day_type_label'] ?? '' }}
                · {{ now()->translatedFormat('l j F Y') }}
            </div>

            <div class="ferry-departure-list">
                @forelse($ferrySnapshot['departures'] ?? [] as $departure)
                    <div @class([
                        'ferry-departure-item',
                        'is-next' => ($departure['status'] ?? '') === 'next',
                        'is-cancelled' => ($departure['is_cancelled'] ?? false) || ($departure['status'] ?? '') === 'cancelled',
                    ])>
                        <div class="ferry-departure-time">{{ $departure['live_time'] ?? $departure['time'] }}</div>
                        <div>
                            <div class="fw-bold">{{ $departure['status_label'] ?? '' }}</div>
                            @include('partials.ferry.departure-notes', ['departure' => $departure])
                            @if(!empty($departure['traffic_message']))
                                <div class="small-muted">{{ $departure['traffic_message'] }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="muted">Inga avgångar idag.</div>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        setTimeout(function () {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
