<?php

namespace Tests\Feature;

use App\Services\Trafikverket\FerryTrafficService;
use App\Support\FerryDirections;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FerryTrafficTest extends TestCase
{
    protected function tearDown(): void
    {
        Cache::flush();
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_ferry_traffic_service_parses_cancelled_departure_alert(): void
    {
        Config::set('trafikverket.api_key', 'test-key');

        Http::fake([
            'api.trafikinfo.trafikverket.se/*' => Http::sequence()
                ->push([
                    'RESPONSE' => [
                        'RESULT' => [
                            [
                                'FerryAnnouncement' => [
                                    [
                                        'RouteName' => 'Hemsöleden',
                                        'DepartureTime' => '2026-06-23T10:15:00',
                                        'TimeTabledDepartureTime' => '2026-06-23T10:00:00',
                                        'DepartureIsCancelled' => true,
                                        'FromHarbor' => ['Name' => 'Strinningen'],
                                        'ToHarbor' => ['Name' => 'Hemsö'],
                                        'Deviation' => ['Text' => 'Inställd på grund av väder.'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ], 200)
                ->push([
                    'RESPONSE' => [
                        'RESULT' => [
                            [
                                'Situation' => [],
                            ],
                        ],
                    ],
                ], 200),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-06-23 09:00:00'));

        $service = app(FerryTrafficService::class);
        $service->refreshForDate(now());

        $alerts = $service->alertsForDate(now(), FerryDirections::TO_ISLAND);

        $this->assertNotEmpty($alerts);
        $this->assertStringContainsString('Inställd', $alerts[0]['title']);
        $this->assertSame('danger', $alerts[0]['level']);

        $status = $service->statusMapForDate(now(), FerryDirections::TO_ISLAND);

        $this->assertTrue($status['10:00']['is_cancelled'] ?? false);
    }

    public function test_ferry_traffic_service_parses_delay(): void
    {
        Config::set('trafikverket.api_key', 'test-key');

        Http::fake([
            'api.trafikinfo.trafikverket.se/*' => Http::response([
                'RESPONSE' => [
                    'RESULT' => [
                        [
                            'FerryAnnouncement' => [
                                [
                                    'RouteName' => 'Hemsöleden',
                                    'DepartureTime' => '2026-06-23T10:12:00',
                                    'TimeTabledDepartureTime' => '2026-06-23T10:00:00',
                                    'DepartureIsCancelled' => false,
                                    'FromHarbor' => ['Name' => 'Strinningen'],
                                    'ToHarbor' => ['Name' => 'Hemsö'],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-06-23 09:55:00'));

        $service = app(FerryTrafficService::class);
        $service->refreshForDate(now());

        $status = $service->statusMapForDate(now(), FerryDirections::TO_ISLAND);

        $this->assertSame(12, $status['10:00']['delay_minutes'] ?? null);
        $this->assertSame('10:12', $status['10:00']['departure_time'] ?? null);
    }

    public function test_sync_command_reports_missing_api_key_gracefully(): void
    {
        Config::set('trafikverket.api_key', null);

        $this->artisan('ferry:sync-traffic')
            ->assertFailed();
    }

    public function test_sync_command_shows_api_error_message(): void
    {
        Config::set('trafikverket.api_key', 'test-key');

        Http::fake([
            'api.trafikinfo.trafikverket.se/*' => Http::response([
                'RESPONSE' => [
                    'RESULT' => [
                        [
                            'ERROR' => [
                                'SOURCE' => 'Request',
                                'MESSAGE' => 'Invalid authentication key',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('ferry:sync-traffic')
            ->expectsOutputToContain('Invalid authentication key')
            ->assertFailed();
    }

    public function test_ferry_traffic_service_reads_route_name_from_nested_field(): void
    {
        Config::set('trafikverket.api_key', 'test-key');

        Http::fake([
            'api.trafikinfo.trafikverket.se/*' => Http::sequence()
                ->push([
                    'RESPONSE' => [
                        'RESULT' => [
                            [
                                'FerryAnnouncement' => [
                                    [
                                        'Route' => ['Name' => 'Hemsöleden'],
                                        'DepartureTime' => '2026-06-23T10:00:00',
                                        'TimeTabledDepartureTime' => '2026-06-23T10:00:00',
                                        'DepartureIsCancelled' => false,
                                        'FromHarbor' => ['Name' => 'Strinningen'],
                                        'ToHarbor' => ['Name' => 'Hemsö'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ], 200)
                ->push(['RESPONSE' => ['RESULT' => [['Situation' => []]]]], 200),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-06-23 09:00:00'));

        $service = app(FerryTrafficService::class);
        $service->refreshForDate(now());

        $status = $service->statusMapForDate(now(), FerryDirections::TO_ISLAND);

        $this->assertArrayHasKey('10:00', $status);
    }

    public function test_extra_departure_triggers_guest_arrival_alert(): void
    {
        Config::set('trafikverket.api_key', 'test-key');

        Http::fake([
            'api.trafikinfo.trafikverket.se/*' => Http::sequence()
                ->push([
                    'RESPONSE' => [
                        'RESULT' => [
                            [
                                'FerryAnnouncement' => [
                                    [
                                        'RouteName' => 'Hemsöleden',
                                        'DepartureTime' => '2026-06-23T10:15:00',
                                        'TimeTabledDepartureTime' => '2026-06-23T10:15:00',
                                        'DepartureIsCancelled' => false,
                                        'FromHarbor' => ['Name' => 'Strinningen'],
                                        'ToHarbor' => ['Name' => 'Hemsö'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ], 200)
                ->push(['RESPONSE' => ['RESULT' => [['Situation' => []]]]], 200),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-06-23 10:20:00'));

        $service = app(FerryTrafficService::class);
        $service->refreshForDate(now());

        $alerts = $service->alertsForDate(now(), FerryDirections::TO_ISLAND);

        $this->assertTrue(collect($alerts)->contains(fn (array $alert) => str_contains($alert['title'], 'Extratur')));

        $lastLive = $service->lastLiveDeparture(now(), FerryDirections::TO_ISLAND);

        $this->assertNotNull($lastLive);
        $this->assertSame('10:15', $lastLive['time']);
        $this->assertTrue($lastLive['is_extra']);
    }
}
