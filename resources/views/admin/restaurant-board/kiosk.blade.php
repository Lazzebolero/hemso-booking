<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
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

        .fw-bold { font-weight: 800; }

        .d-flex { display: flex; }

        .flex-wrap { flex-wrap: wrap; }

        .align-items-center { align-items: center; }

        .gap-1 { gap: 0.25rem; }

        .small-muted {
            color: var(--text-soft);
            font-size: 0.88rem;
        }

        .badge-soft {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 6px 11px;
            font-size: 0.78rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .badge-soft-success {
            background: rgba(5, 150, 105, 0.12);
            color: #047857;
        }

        .badge-soft-warning {
            background: rgba(217, 119, 6, 0.14);
            color: #b45309;
        }

        .badge-soft-danger {
            background: rgba(220, 38, 38, 0.12);
            color: #b91c1c;
        }

        .badge-soft-secondary {
            background: rgba(100, 116, 139, 0.12);
            color: #334155;
        }

        .tour-meal-badge,
        .tour-ferry-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: 0.74rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .tour-meal-badge {
            background: rgba(217, 119, 6, 0.14);
            color: #b45309;
        }

        .tour-ferry-badge {
            background: rgba(37, 99, 235, 0.12);
            color: #1d4ed8;
        }

        .restaurant-upcoming-divider {
            margin: 14px 0;
            border-top: 1px solid var(--brand-line-soft);
        }

        .restaurant-upcoming-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .restaurant-upcoming-table thead th {
            text-align: left;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-soft);
            padding: 8px 6px;
            border-bottom: 1px solid var(--brand-line-soft);
            vertical-align: bottom;
        }

        .restaurant-upcoming-table tbody td {
            padding: 10px 6px;
            border-bottom: 1px solid #edf2f7;
            vertical-align: top;
            word-break: break-word;
            font-size: 0.92rem;
        }

        .restaurant-upcoming-table tbody tr:last-child td {
            border-bottom: none;
        }

        .restaurant-occupancy-bar {
            width: 100%;
            max-width: 72px;
            height: 8px;
            border-radius: 999px;
            overflow: hidden;
            background: #e2e8f0;
        }

        .restaurant-occupancy-bar > div {
            height: 100%;
            border-radius: 999px;
        }

        .board-toggle-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .board-toggle-btn {
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

        .board-toggle-btn.is-active {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .board-upcoming-head {
            display: flex;
            justify-content: space-between;
            align-items: start;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

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
                <div class="board-updated" id="board-updated">Senast uppdaterad: {{ $nowLabel }}</div>

                <form method="POST" action="{{ route('restaurant-statistics.logout') }}" class="logout-form">
                    @csrf
                    <button type="submit">Logga ut</button>
                </form>
            </div>
        </div>

        <div id="board-live" data-poll-url="{{ $boardPollUrl ?? '' }}">
            @includeIf('admin.restaurant-board.partials.kiosk-live')
        </div>
    </div>

    <script>
        (function () {
            const root = document.getElementById('board-live');
            const updated = document.getElementById('board-updated');

            if (!root || !root.dataset.pollUrl) {
                return;
            }

            async function poll() {
                try {
                    const response = await fetch(root.dataset.pollUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();

                    if (updated && data.now_label) {
                        updated.textContent = 'Senast uppdaterad: ' + data.now_label;
                    }

                    if (typeof data.html === 'string') {
                        root.innerHTML = data.html;
                    }
                } catch (error) {
                    // Keep the last rendered board if the poll fails.
                }
            }

            window.setInterval(poll, 30000);
        })();
    </script>
</body>
</html>
