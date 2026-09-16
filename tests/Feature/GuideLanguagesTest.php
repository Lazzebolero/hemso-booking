<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Language;
use App\Models\Role;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuideLanguagesTest extends TestCase
{
    public function test_admin_can_assign_guide_languages_on_user_update(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();

        $english = Language::query()->create([
            'name' => 'Engelska',
            'code' => 'en',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $swedish = Language::query()->where('code', 'sv')->firstOrFail();

        $guide = User::factory()->create([
            'name' => 'Guide Anna',
            'email' => 'anna@example.com',
            'password' => Hash::make('password'),
        ]);
        $guide->assignRoles([$guideRole]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.users.update', $guide), [
                'name' => 'Guide Anna',
                'email' => 'anna@example.com',
                'phone' => null,
                'roles' => [Roles::GUIDE],
                'guide_languages' => [$swedish->id, $english->id],
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $guide->refresh()->load('guideLanguages');

        $this->assertSame(['en', 'sv'], $guide->guideLanguages->pluck('code')->sort()->values()->all());
        $this->assertSame('SV, EN', $guide->guideLanguageLabel());
    }

    public function test_guide_languages_are_cleared_when_guide_role_is_removed(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $swedish = Language::query()->where('code', 'sv')->firstOrFail();

        $user = User::factory()->create();
        $user->assignRoles([$guideRole]);
        $user->guideLanguages()->sync([$swedish->id]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => null,
                'roles' => [Roles::HOST],
                'guide_languages' => [$swedish->id],
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame(0, $user->fresh()->guideLanguages()->count());
    }

    public function test_admin_and_host_can_view_guide_language_overview(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $host = $this->userWithRole(Roles::HOST);
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $swedish = Language::query()->where('code', 'sv')->firstOrFail();

        $guide = User::factory()->create(['name' => 'Språkguide']);
        $guide->assignRoles([$guideRole]);
        $guide->guideLanguages()->sync([$swedish->id]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.guide-languages.index'))
            ->assertOk()
            ->assertSee('Guide på språk', false)
            ->assertSee('Språkguide', false)
            ->assertSee('SV', false);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.guide-languages.index'))
            ->assertOk()
            ->assertSee('Språkguide', false);
    }

    public function test_guide_availability_includes_language_codes_in_label(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $swedish = Language::query()->where('code', 'sv')->firstOrFail();

        $english = Language::query()->create([
            'name' => 'Engelska',
            'code' => 'en',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $guide = User::factory()->create(['name' => 'Erik Guide']);
        $guide->assignRoles([$guideRole]);
        $guide->guideLanguages()->sync([$swedish->id, $english->id]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->getJson(route('admin.guides.availability', [
                'date' => now()->addDay()->toDateString(),
                'start_time' => '10:00',
            ]));

        $response->assertOk();

        $guideRow = collect($response->json())->firstWhere('id', $guide->id);

        $this->assertNotNull($guideRow);
        $this->assertSame(['SV', 'EN'], $guideRow['language_codes']);
        $this->assertStringContainsString('[SV, EN]', $guideRow['label']);
    }

    public function test_guide_availability_flags_language_mismatch_for_tour_bookings(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $swedish = Language::query()->where('code', 'sv')->firstOrFail();

        $english = Language::query()->create([
            'name' => 'Engelska',
            'code' => 'en',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $matchingGuide = User::factory()->create(['name' => 'Svenskguide']);
        $matchingGuide->assignRoles([$guideRole]);
        $matchingGuide->guideLanguages()->sync([$swedish->id, $english->id]);

        $limitedGuide = User::factory()->create(['name' => 'Begränsad guide']);
        $limitedGuide->assignRoles([$guideRole]);
        $limitedGuide->guideLanguages()->sync([$swedish->id]);

        $tour = Tour::query()->create([
            'title' => 'Språktur',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        $booking = Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Engelsk grupp',
            'men_count' => 5,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 5,
            'status' => 'confirmed',
        ]);
        $booking->languages()->sync([$english->id]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->getJson(route('admin.guides.availability', [
                'date' => $tour->tour_date->toDateString(),
                'start_time' => '10:00',
                'tour_id' => $tour->id,
            ]));

        $response->assertOk();

        $limitedRow = collect($response->json())->firstWhere('id', $limitedGuide->id);
        $matchingRow = collect($response->json())->firstWhere('id', $matchingGuide->id);

        $this->assertTrue($limitedRow['has_language_mismatch']);
        $this->assertSame(['EN'], $limitedRow['missing_language_codes']);
        $this->assertStringContainsString('[Saknar EN]', $limitedRow['label']);

        $this->assertFalse($matchingRow['has_language_mismatch']);
        $this->assertSame([], $matchingRow['missing_language_codes']);
    }

    public function test_tour_show_displays_language_mismatch_warning(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $swedish = Language::query()->where('code', 'sv')->firstOrFail();

        $english = Language::query()->create([
            'name' => 'Engelska',
            'code' => 'en',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $guide = User::factory()->create(['name' => 'Endast svenska']);
        $guide->assignRoles([$guideRole]);
        $guide->guideLanguages()->sync([$swedish->id]);

        $tour = Tour::query()->create([
            'title' => 'Varningstur',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
            'guide_id' => $guide->id,
        ]);

        $booking = Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Engelsk grupp',
            'men_count' => 4,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 4,
            'status' => 'confirmed',
        ]);
        $booking->languages()->sync([$english->id]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.show', $tour))
            ->assertOk()
            ->assertSee('Språkmismatch', false)
            ->assertSee('Guiden saknar bokade språk: EN', false);
    }

    public function test_booking_store_warns_when_guide_lacks_booking_language(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $swedish = Language::query()->where('code', 'sv')->firstOrFail();

        $english = Language::query()->create([
            'name' => 'Engelska',
            'code' => 'en',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $guide = User::factory()->create(['name' => 'Endast svenska']);
        $guide->assignRoles([$guideRole]);
        $guide->guideLanguages()->sync([$swedish->id]);

        $tour = Tour::query()->create([
            'title' => 'Bokningstur',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
            'guide_id' => $guide->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.bookings.store'), [
                'tour_id' => $tour->id,
                'booking_name' => 'Engelsk bokning',
                'men_count' => 4,
                'women_count' => 0,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
                'languages' => [$english->id],
            ])
            ->assertRedirect(route('admin.bookings.index'))
            ->assertSessionHas('success')
            ->assertSessionHas('warning', 'Bokningen har språk EN. Guiden Endast svenska saknar EN.');
    }

    public function test_booking_update_warns_when_guide_lacks_new_booking_language(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $swedish = Language::query()->where('code', 'sv')->firstOrFail();

        $english = Language::query()->create([
            'name' => 'Engelska',
            'code' => 'en',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $guide = User::factory()->create(['name' => 'Svenskguide']);
        $guide->assignRoles([$guideRole]);
        $guide->guideLanguages()->sync([$swedish->id]);

        $tour = Tour::query()->create([
            'title' => 'Uppdateringstur',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
            'guide_id' => $guide->id,
        ]);

        $booking = Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Svensk bokning',
            'men_count' => 2,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 2,
            'status' => 'confirmed',
        ]);
        $booking->languages()->sync([$swedish->id]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.bookings.update', $booking), [
                'tour_id' => $tour->id,
                'booking_name' => 'Svensk bokning',
                'men_count' => 2,
                'women_count' => 0,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
                'languages' => [$english->id],
            ])
            ->assertRedirect(route('admin.bookings.index'))
            ->assertSessionHas('success')
            ->assertSessionHas('warning', 'Bokningen har språk EN. Guiden Svenskguide saknar EN.');
    }

    public function test_quick_booking_store_warns_when_guide_lacks_booking_language(): void
    {
        $host = $this->userWithRole(Roles::HOST);
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $swedish = Language::query()->where('code', 'sv')->firstOrFail();

        $english = Language::query()->create([
            'name' => 'Engelska',
            'code' => 'en',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $guide = User::factory()->create(['name' => 'Bokningssekvensguide']);
        $guide->assignRoles([$guideRole]);
        $guide->guideLanguages()->sync([$swedish->id]);

        $guidedType = TourType::query()->create([
            'name' => 'Guidad visning test',
            'sort_order' => 1,
            'is_active' => true,
            'include_in_booking_sequence' => true,
            'default_duration_minutes' => 75,
        ]);

        $tour = Tour::query()->create([
            'title' => 'Snabb bokningstur',
            'tour_type_id' => $guidedType->id,
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
            'guide_id' => $guide->id,
        ]);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->post(route('host.bookings.quick-store'), [
                'tour_id' => $tour->id,
                'men_count' => 3,
                'women_count' => 0,
                'youth_count' => 0,
                'child_count' => 0,
                'languages' => [$english->id],
            ])
            ->assertRedirect(route('host.bookings.quick-create'))
            ->assertSessionHas('success')
            ->assertSessionHas('warning', 'Bokningen har språk EN. Guiden Bokningssekvensguide saknar EN.');
    }

    public function test_guide_languages_page_shows_setup_message_when_table_is_missing(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        Schema::drop('guide_language');

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.guide-languages.index'))
            ->assertOk()
            ->assertSee('Guide på språk', false)
            ->assertSee('guide_language', false)
            ->assertSee('php artisan migrate --force', false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
