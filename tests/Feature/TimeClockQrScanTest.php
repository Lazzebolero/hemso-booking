<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TimeClockLocationPayload;
use App\Services\TimeEntryDeviationService;
use App\Support\Roles;
use Tests\TestCase;

class TimeClockQrScanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'time_clock.stations.entrance.token' => 'test-entrance-token',
            'time_clock.stations.restaurant.token' => 'test-restaurant-token',
            'time_clock.facility_latitude' => 60.0,
            'time_clock.facility_longitude' => 15.0,
        ]);
    }

    public function test_guide_can_open_entrance_scan_page(): void
    {
        $user = $this->userWithRole(Roles::GUIDE);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('time.scan', ['token' => 'test-entrance-token']))
            ->assertOk()
            ->assertSee('Entré', false);
    }

    public function test_guide_can_open_restaurant_scan_page(): void
    {
        $user = $this->userWithRole(Roles::GUIDE);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('time.scan', ['token' => 'test-restaurant-token']))
            ->assertOk()
            ->assertSee('Restaurang', false);
    }

    public function test_guide_can_open_entrance_station_shortcut(): void
    {
        $user = $this->userWithRole(Roles::GUIDE);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('time.station', ['station' => 'entrance']))
            ->assertOk()
            ->assertSee('Stämpla in', false);
    }

    public function test_time_index_shows_both_stations_for_guide(): void
    {
        $user = $this->userWithRole(Roles::GUIDE);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('time.index'))
            ->assertOk()
            ->assertSee('Stämpling via QR', false)
            ->assertSee('Entré', false)
            ->assertSee('Restaurang', false);
    }

    public function test_restaurant_can_open_entrance_scan_page(): void
    {
        $user = $this->userWithRole(Roles::RESTAURANT);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::RESTAURANT])
            ->get(route('time.scan', ['token' => 'test-entrance-token']))
            ->assertOk()
            ->assertSee('Entré', false);
    }

    public function test_restaurant_can_clock_in_via_restaurant_qr_with_gps(): void
    {
        $user = $this->userWithRole(Roles::RESTAURANT);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::RESTAURANT])
            ->post(route('time.clock-in'), [
                'clock_station' => 'restaurant',
                'scan_token' => 'test-restaurant-token',
                'latitude' => 60.001,
                'longitude' => 15.001,
                'accuracy_m' => 800,
                'location_status' => TimeClockLocationPayload::STATUS_OK,
            ])
            ->assertRedirect(route('time.scan', ['token' => 'test-restaurant-token']))
            ->assertSessionHas('success');

        $entry = TimeEntry::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($entry);
        $this->assertSame('restaurant', $entry->clock_in_station);
        $this->assertSame(TimeClockLocationPayload::STATUS_OK, $entry->clock_in_location_status);
        $this->assertEqualsWithDelta(60.001, (float) $entry->clock_in_latitude, 0.0001);
    }

    public function test_restaurant_can_clock_in_at_entrance_and_station_is_saved(): void
    {
        $user = $this->userWithRole(Roles::RESTAURANT);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::RESTAURANT])
            ->post(route('time.clock-in'), [
                'clock_station' => 'entrance',
                'scan_token' => 'test-entrance-token',
                'location_status' => TimeClockLocationPayload::STATUS_DENIED,
            ])
            ->assertRedirect(route('time.scan', ['token' => 'test-entrance-token']))
            ->assertSessionHas('success');

        $entry = TimeEntry::query()->where('user_id', $user->id)->first();
        $this->assertSame('entrance', $entry->clock_in_station);
    }

    public function test_clock_out_saves_station_separate_from_clock_in(): void
    {
        $user = $this->userWithRole(Roles::HOST);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->post(route('time.clock-in'), [
                'clock_station' => 'entrance',
                'scan_token' => 'test-entrance-token',
                'location_status' => TimeClockLocationPayload::STATUS_DENIED,
            ])
            ->assertSessionHas('success');

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->post(route('time.clock-out'), [
                'clock_station' => 'restaurant',
                'scan_token' => 'test-restaurant-token',
                'location_status' => TimeClockLocationPayload::STATUS_DENIED,
            ])
            ->assertSessionHas('success');

        $entry = TimeEntry::query()->where('user_id', $user->id)->first();
        $this->assertSame('entrance', $entry->clock_in_station);
        $this->assertSame('restaurant', $entry->clock_out_station);
    }

    public function test_clock_in_without_gps_is_allowed_and_flagged(): void
    {
        $user = $this->userWithRole(Roles::HOST);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->post(route('time.clock-in'), [
                'clock_station' => 'entrance',
                'scan_token' => 'test-entrance-token',
                'location_status' => TimeClockLocationPayload::STATUS_DENIED,
            ])
            ->assertRedirect(route('time.scan', ['token' => 'test-entrance-token']))
            ->assertSessionHas('success');

        $entry = TimeEntry::query()->where('user_id', $user->id)->first();
        $this->assertSame('entrance', $entry->clock_in_station);
        $this->assertSame(TimeClockLocationPayload::STATUS_DENIED, $entry->clock_in_location_status);
        $this->assertNull($entry->clock_in_latitude);
    }

    public function test_far_from_facility_adds_deviation_for_admin_review(): void
    {
        $user = $this->userWithRole(Roles::GUIDE);

        $entry = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'clock_in_station' => 'entrance',
            'clock_in_latitude' => 59.0,
            'clock_in_longitude' => 14.0,
            'clock_in_location_status' => TimeClockLocationPayload::STATUS_OK,
        ]);

        $deviations = TimeEntryDeviationService::forEntry($entry);

        $this->assertTrue(collect($deviations)->contains(fn (array $d) => $d['code'] === 'far_from_facility'));
    }

    public function test_invalid_scan_token_returns_not_found(): void
    {
        $user = $this->userWithRole(Roles::GUIDE);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('time.scan', ['token' => 'unknown-token']))
            ->assertNotFound();
    }

    public function test_guide_cannot_clock_in_without_qr_station(): void
    {
        $user = $this->userWithRole(Roles::GUIDE);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('time.clock-in'))
            ->assertRedirect(route('time.index'))
            ->assertSessionHas('warning');
    }

    public function test_restaurant_role_can_access_time_index(): void
    {
        $user = $this->userWithRole(Roles::RESTAURANT);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::RESTAURANT])
            ->get(route('time.index'))
            ->assertOk();
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
