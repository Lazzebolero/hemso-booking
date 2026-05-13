<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="30">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurang statistik</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <style>
        :root {
            --brand-bg: #f8fafc;
            --brand-line-soft: #dbe3ee;
            --text-main: #0f172a;
            --text-soft: #64748b;
            --card-bg: #ffffff;
            --accent: #0f766e;
            --accent-soft: rgba(15, 118, 110, 0.12);
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

        .board-top-right {
            display: flex;
            flex-direction: column;
            align-items: end;
            gap: 8px;
        }

        .board-updated {
            font-size: 0.95rem;
            color: var(--text-soft);
            font-weight: 700;
            white-space: nowrap;
        }

        .logout-form button {
            background: #fff;
            color: var(--text-soft);
            border: 1px solid var(--brand-line-soft);
            border-radius: 12px;
            padding: 8px 12px;
            cursor: pointer;
            font-weight: 700;
        }

        .board-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .board-stat {
            background: var(--card-bg);
            border: 1px solid var(--brand-line-soft);
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
        }

        .board-stat-label {
            color: var(--text-soft);
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .board-stat-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
        }

        .board-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.55fr) minmax(340px, 0.9fr);
            gap: 16px;
            align-items: start;
        }

        .board-grid {
            display: grid;
            grid-template-columns: minmax(360px, 0.95fr) minmax(0, 1.3fr);
            gap: 16px;
            align-items: start;
        }

        .panel {
            background: var(--card-bg);
            border: 1px solid var(--brand-line-soft);
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
        }

        .panel-title {
            margin: 0 0 14px;
            font-size: 1.2rem;
            font-weight: 800;
        }

        .tour-card {
            border: 1px solid var(--brand-line-soft);
            border-radius: 16px;
            padding: 16px;
            background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%);
            margin-bottom: 12px;
        }

        .tour-card:last-child { margin-bottom: 0; }

        .tour-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: start;
        }

        .tour-title {
            font-size: 1.05rem;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .tour-meta {
            color: var(--text-soft);
            font-size: 0.92rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 6px 11px;
            font-size: 0.8rem;
            font-weight: 800;
            background: var(--accent-soft);
            color: var(--accent);
            white-space: nowrap;
        }

        .tour-metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
        }

        .metric {
            background: #fff;
            border: 1px solid var(--brand-line-soft);
            border-radius: 14px;
            padding: 12px 13px;
        }

        .metric-label {
            color: var(--text-soft);
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .metric-value {
            font-size: 1.1rem;
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

        .fw-semibold { font-weight: 700; }

        .staff-panel .panel-title { margin-bottom: 10px; }

        .function-group {
            margin-top: 16px;
            border-top: 1px solid var(--brand-line-soft);
            padding-top: 16px;
        }

        .function-group:first-child {
            margin-top: 0;
            border-top: 0;
            padding-top: 0;
        }

        .function-title {
            font-size: 1rem;
            font-weight: 800;
            margin-bottom: 10px;
            color: var(--accent);
        }

        .staff-item {
            border: 1px solid var(--brand-line-soft);
            border-radius: 14px;
            padding: 12px;
            background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%);
            margin-bottom: 10px;
        }

        .staff-item:last-child { margin-bottom: 0; }

        .staff-name {
            font-weight: 800;
            margin-bottom: 4px;
        }

        .empty { color: var(--text-soft); }

        @media (max-width: 1350px) {
            .board-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .board-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1200px) {
            .board-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {
            .board-top {
                flex-direction: column;
            }

            .board-top-right {
                align-items: start;
            }

            .board-stats {
                grid-template-columns: 1fr;
            }

            .tour-metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="board-shell">
        <div class="board-top">
            <div>
                <h1 class="board-title">Restaurang – dagens turer och bemanning</h1>
                <div class="board-subtitle">Lägesbild för pågående och kommande turer samt personal idag.</div>
            </div>

            <div class="board-top-right">
                <div class="board-updated">Senast uppdaterad: {{ $nowLabel }}</div>

                <form method="POST" action="{{ route('restaurant-statistics.logout') }}" class="logout-form">
                    @csrf
                    <button type="submit">Logga ut</button>
                </form>
            </div>
        </div>

        <div class="board-stats">
            <div class="board-stat">
                <div class="board-stat-label">Pågående turer</div>
                <div class="board-stat-value">{{ $ongoingTours->count() }}</div>
            </div>

            <div class="board-stat">
                <div class="board-stat-label">Gäster på pågående turer</div>
                <div class="board-stat-value">{{ $totalOngoingGuests }}</div>
            </div>

            <div class="board-stat">
                <div class="board-stat-label">Män på tur</div>
                <div class="board-stat-value">{{ $ongoingParticipantBreakdown['men'] ?? 0 }}</div>
            </div>

            <div class="board-stat">
                <div class="board-stat-label">Kvinnor på tur</div>
                <div class="board-stat-value">{{ $ongoingParticipantBreakdown['women'] ?? 0 }}</div>
            </div>

            <div class="board-stat">
                <div class="board-stat-label">Ungdomar på tur</div>
                <div class="board-stat-value">{{ $ongoingParticipantBreakdown['youth'] ?? 0 }}</div>
            </div>

            <div class="board-stat">
                <div class="board-stat-label">Barn på tur</div>
                <div class="board-stat-value">{{ $ongoingParticipantBreakdown['children'] ?? 0 }}</div>
            </div>

            <div class="board-stat">
                <div class="board-stat-label">Gäster på kommande turer</div>
                <div class="board-stat-value">{{ $totalUpcomingGuests }}</div>
            </div>

            <div class="board-stat">
                <div class="board-stat-label">Besökare totalt idag</div>
                <div class="board-stat-value">{{ $totalOngoingGuests + $totalUpcomingGuests }}</div>
            </div>
        </div>

        <div class="board-layout">
            <div class="board-grid">
                <div class="panel">
                    <h2 class="panel-title">Pågående turer</h2>

                    @forelse($ongoingTours as $tour)
                        <div class="tour-card">
                            <div class="tour-row">
                                <div>
                                    <div class="tour-title">{{ $tour->title }}</div>
                                    <div class="tour-meta">
                                        @if(!empty($tour->started_at))
                                            Turen startade {{ \Carbon\Carbon::parse($tour->started_at)->format('H:i') }}
                                        @else
                                            Start {{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}
                                        @endif
                                        • {{ $tour->guide?->name ?? 'Ej tilldelad' }}
                                    </div>
                                </div>

                                <div class="badge">Pågående</div>
                            </div>

                            <div class="tour-metrics">
                                <div class="metric">
                                    <div class="metric-label">Bokade</div>
                                    <div class="metric-value">{{ $tour->booked_people_count }}</div>
                                </div>

                                <div class="metric">
                                    <div class="metric-label">Beräknas klar</div>
                                    <div class="metric-value">{{ $tour->estimated_end_time }}</div>
                                </div>

                                <div class="metric">
                                    <div class="metric-label">Tid kvar</div>
                                    <div class="metric-value">{{ $tour->remaining_to_end }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="muted">Inga pågående turer just nu.</div>
                    @endforelse
                </div>

                <div class="panel">
                    <h2 class="panel-title">Kommande turer</h2>

                    <table>
                        <thead>
                            <tr>
                                <th style="width:90px;">Tid</th>
                                <th>Tur</th>
                                <th style="width:90px;">Antal</th>
                                <th style="width:140px;">Beräknas ut</th>
                                <th style="width:130px;">Startar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($upcomingTours as $tour)
                                <tr>
                                    <td class="fw-semibold">{{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '-' }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $tour->title }}</div>
                                        <div class="muted">{{ $tour->guide?->name ?? 'Ej tilldelad' }}</div>
                                    </td>
                                    <td>{{ $tour->booked_people_count }}</td>
                                    <td>{{ $tour->estimated_end_time }}</td>
                                    <td>{{ $tour->time_until_start }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="muted">Inga kommande turer idag.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel staff-panel">
                <h2 class="panel-title">Personal idag</h2>

                @forelse($todayStaffByFunction as $functionKey => $shifts)
                    <div class="function-group">
                        <div class="function-title">
                            {{ $restaurantFunctions[$functionKey] ?? ucfirst($functionKey) }}
                        </div>

                        @foreach($shifts as $shift)
                            <div class="staff-item">
                                <div class="staff-name">{{ $shift->user->name ?? 'Okänd' }}</div>
                                <div class="muted">
                                    {{ substr($shift->start_time, 0, 5) }}–{{ $shift->end_time ? substr($shift->end_time, 0, 5) : '--:--' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <div class="empty">Ingen restaurangpersonal schemalagd idag.</div>
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