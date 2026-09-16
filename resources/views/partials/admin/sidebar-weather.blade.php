@if(!empty($sidebarWeather) && Route::has($adminHostPrefix . '.weather-forecast.index'))
    @once
        <style>
            .sidebar-weather-card {
                display: block;
                margin: 0 0 0.9rem;
                padding: 0.8rem 0.85rem;
                border-radius: 14px;
                background: rgba(255, 255, 255, 0.06);
                border: 1px solid rgba(255, 255, 255, 0.1);
                color: #f8fafc;
                text-decoration: none;
                transition: background 0.18s ease, border-color 0.18s ease;
            }

            .sidebar-weather-card:hover {
                background: rgba(255, 255, 255, 0.1);
                border-color: rgba(125, 211, 252, 0.24);
                color: #fff;
            }

            .sidebar-weather-card-active {
                background: linear-gradient(135deg, rgba(59, 130, 246, 0.18), rgba(37, 99, 235, 0.22));
                border-color: rgba(125, 211, 252, 0.24);
            }

            .sidebar-weather-station {
                font-size: 0.72rem;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: rgba(148, 163, 184, 0.92);
                margin-bottom: 0.35rem;
                font-weight: 700;
            }

            .sidebar-weather-main {
                font-size: 0.95rem;
                font-weight: 700;
                line-height: 1.35;
                color: #fff;
            }

            .sidebar-weather-sub {
                margin-top: 0.35rem;
                font-size: 0.82rem;
                line-height: 1.35;
                color: rgba(226, 232, 240, 0.82);
            }

            .sidebar-weather-link {
                display: inline-flex;
                align-items: center;
                gap: 0.3rem;
                margin-top: 0.55rem;
                font-size: 0.8rem;
                font-weight: 700;
                color: #bfdbfe;
            }

            .sidebar-weather-alert {
                margin-top: 0.45rem;
                padding: 0.45rem 0.55rem;
                border-radius: 10px;
                font-size: 0.8rem;
                line-height: 1.35;
                font-weight: 600;
                background: rgba(245, 158, 11, 0.16);
                border: 1px solid rgba(245, 158, 11, 0.35);
                color: #fde68a;
            }

            .sidebar-weather-alert-severe {
                background: rgba(239, 68, 68, 0.18);
                border-color: rgba(239, 68, 68, 0.4);
                color: #fecaca;
            }
        </style>
    @endonce

    <a href="{{ route($adminHostPrefix . '.weather-forecast.index') }}"
       class="sidebar-weather-card {{ request()->routeIs('admin.weather-forecast.*') || request()->routeIs('host.weather-forecast.*') ? 'sidebar-weather-card-active' : '' }}">
        <div class="sidebar-weather-station">{{ $sidebarWeather['station_label'] ?? 'Lungö' }}</div>

        <div class="sidebar-weather-main">
            @if(!empty($sidebarWeather['unavailable']))
                Kunde inte hämta väder just nu
            @elseif(isset($sidebarWeather['temp_now']))
                {{ rtrim(rtrim(number_format((float) $sidebarWeather['temp_now'], 1, '.', ''), '0'), '.') }}° {{ $sidebarWeather['temp_now_label'] ?? 'nu' }}
            @elseif(!empty($sidebarWeather['today_forecast_text']))
                Idag · {{ $sidebarWeather['today_forecast_text'] }}
            @else
                Väderdata tillgänglig
            @endif
        </div>

        @if(empty($sidebarWeather['unavailable']) && !empty($sidebarWeather['today_forecast_text']) && isset($sidebarWeather['temp_now']))
            <div class="sidebar-weather-sub">
                Idag · {{ $sidebarWeather['today_forecast_text'] }}
            </div>
        @endif

        @if(!empty($sidebarWeather['tomorrow_text']))
            <div class="sidebar-weather-sub">
                Imorgon · {{ $sidebarWeather['tomorrow_text'] }}
            </div>
        @endif

        @if(!empty($sidebarWeather['warning_alert']))
            @php
                $alertSeverity = $sidebarWeather['warning_alert']['severity'] ?? 'MESSAGE';
            @endphp
            <div class="sidebar-weather-alert {{ in_array($alertSeverity, ['RED', 'ORANGE'], true) ? 'sidebar-weather-alert-severe' : '' }}">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                {{ $sidebarWeather['warning_alert']['summary'] ?? 'Aktiv vädervarning' }}
            </div>
        @endif

        <span class="sidebar-weather-link">
            Väder & prognos
            <i class="bi bi-arrow-right-short"></i>
        </span>
    </a>
@endif
