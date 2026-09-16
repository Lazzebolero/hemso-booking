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
                <div class="board-stat-value">{{ $totalTodayVisitors }}</div>
            </div>
        </div>

        @include('partials.ferry.status-card', [
            'snapshot' => $ferrySnapshot ?? [],
            'timetableUrl' => $ferryTimetableUrl ?? null,
            'variant' => 'kiosk',
        ])

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
                                    </div>
                                    @include('partials.admin.tour-guide-display-block', ['tour' => $tour, 'metaClass' => 'tour-meta'])
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
                    <h2 class="panel-title">Kommande turer idag</h2>
                    <div class="board-subtitle" style="margin-bottom: 12px;">Planerade turer kvar idag som inte har startat ännu.</div>

                    @includeIf('partials.admin.restaurant-upcoming-tours-table', [
                        'tours' => $upcomingToursToday ?? collect(),
                        'showTourDate' => false,
                        'useAppTable' => false,
                        'emptyMessage' => 'Inga fler kommande turer idag.',
                    ])

                    <div class="restaurant-upcoming-divider"></div>

                    <div class="board-upcoming-head">
                        <div>
                            <h2 class="panel-title" style="margin-bottom: 6px;">Kommande turer</h2>
                            <div class="board-subtitle">
                                Imorgon och de närmaste {{ $aheadDays ?? 7 }} dagarna
                                @if(!empty($aheadEndDate))
                                    (t.o.m. {{ \Carbon\Carbon::parse($aheadEndDate)->format('Y-m-d') }}).
                                @endif
                            </div>
                        </div>

                        <div class="board-toggle-group">
                            @php
                                $kioskRoute = $restaurantBoardKioskRoute ?? \App\Support\ActiveRole::routeName('restaurant-board.kiosk');
                            @endphp
                            <a
                                href="{{ route($kioskRoute, ['ahead_days' => 7]) }}"
                                class="board-toggle-btn {{ ($aheadDays ?? 7) === 7 ? 'is-active' : '' }}"
                            >
                                7 dagar
                            </a>
                            <a
                                href="{{ route($kioskRoute, ['ahead_days' => 30]) }}"
                                class="board-toggle-btn {{ ($aheadDays ?? 7) === 30 ? 'is-active' : '' }}"
                            >
                                30 dagar
                            </a>
                        </div>
                    </div>

                    @includeIf('partials.admin.restaurant-upcoming-tours-table', [
                        'tours' => $upcomingToursAhead ?? collect(),
                        'showTourDate' => true,
                        'useAppTable' => false,
                        'emptyMessage' => 'Inga kommande turer de valda dagarna.',
                    ])
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

        <div class="panel" style="margin-top: 16px;">
            <h2 class="panel-title">Dagens turer</h2>
            <div class="board-subtitle" style="margin-bottom: 14px;">Kompakt översikt över dagens schema.</div>

            @includeIf('partials.admin.restaurant-today-tours-table', [
                'tours' => $todayTours ?? collect(),
                'useAppTable' => false,
            ])
        </div>
