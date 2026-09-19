<?php

namespace Tests\Feature;

use App\Models\RestaurantFunction;
use App\Models\Role;
use App\Models\ShiftRoleDefault;
use App\Models\User;
use App\Models\WorkShift;
use App\Support\Roles;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RestaurantFunctionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_manage_restaurant_functions(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->from(route('admin.restaurant-functions.index'))
            ->post(route('admin.restaurant-functions.store'), [
                'slug' => 'kok',
                'name' => 'Kök',
                'sort_order' => 5,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.restaurant-functions.index'));

        $this->assertDatabaseHas('restaurant_functions', [
            'slug' => 'kok',
            'name' => 'Kök',
        ]);

        $function = RestaurantFunction::query()->where('slug', 'kok')->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->from(route('admin.restaurant-functions.index'))
            ->put(route('admin.restaurant-functions.update', $function), [
                'slug' => 'kok',
                'name' => 'Kök & servering',
                'sort_order' => 5,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.restaurant-functions.index'));

        $this->assertSame('Kök & servering', WorkShift::restaurantFunctions()['kok'] ?? RestaurantFunction::activeOptions()['kok']);
    }

    public function test_admin_can_update_role_and_function_default_times(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.restaurant-functions.index'))
            ->assertOk()
            ->assertSee('Standardtider för roller', false)
            ->assertSee('Guide', false)
            ->assertSee('Värd', false);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->from(route('admin.restaurant-functions.index'))
            ->put(route('admin.restaurant-functions.role-defaults'), [
                'roles' => [
                    'admin' => ['default_start_time' => '08:00', 'default_end_time' => '16:00'],
                    'host' => ['default_start_time' => '09:00', 'default_end_time' => '17:00'],
                    'guide' => ['default_start_time' => '10:00', 'default_end_time' => '15:00'],
                    'elev' => ['default_start_time' => '11:00', 'default_end_time' => ''],
                ],
            ])
            ->assertRedirect(route('admin.restaurant-functions.index'));

        $this->assertSame('10:00-15:00', ShiftRoleDefault::timeRangeFor(Roles::GUIDE));
        $this->assertSame('09:00-17:00', ShiftRoleDefault::timeRangeFor(Roles::HOST));
        $this->assertSame('11:00', ShiftRoleDefault::timeRangeFor(Roles::ELEV));

        $function = RestaurantFunction::query()->create([
            'slug' => 'buffe',
            'name' => 'Buffé',
            'sort_order' => 70,
            'is_active' => true,
            'default_start_time' => '08:00',
            'default_end_time' => '14:00',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->from(route('admin.restaurant-functions.index'))
            ->put(route('admin.restaurant-functions.update', $function), [
                'slug' => 'buffe',
                'name' => 'Buffé',
                'sort_order' => 70,
                'is_active' => '1',
                'default_start_time' => '08:30',
                'default_end_time' => '13:30',
            ])
            ->assertRedirect(route('admin.restaurant-functions.index'));

        $this->assertSame('08:30-13:30', RestaurantFunction::timeRangeFor('buffe'));
    }

    public function test_work_shift_dropdown_uses_active_restaurant_functions(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        RestaurantFunction::query()->create([
            'slug' => 'kok',
            'name' => 'Kök',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.work-shifts.index'))
            ->assertOk()
            ->assertSee('value="kok"', false)
            ->assertSee('Kök', false);
    }

    public function test_work_shift_pages_read_functions_from_database_not_stale_cache(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $cook = $this->userWithRole(Roles::RESTAURANT);

        Cache::forever(RestaurantFunction::CACHE_KEY.'.active', [
            'kassa' => 'Kassa',
        ]);

        DB::table('restaurant_functions')->insert([
            'slug' => 'buffe',
            'name' => 'Buffé',
            'sort_order' => 25,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.work-shifts.index'))
            ->assertOk()
            ->assertSee('value="buffe"', false)
            ->assertSee('Buffé', false);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.work-shifts.person', ['user_id' => $cook->id]))
            ->assertOk()
            ->assertSee('value="buffe"', false)
            ->assertSee('Buffé', false);
    }

    public function test_cannot_delete_restaurant_function_in_use(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $restaurantUser = $this->userWithRole(Roles::RESTAURANT);

        $function = RestaurantFunction::query()->create([
            'slug' => 'kok',
            'name' => 'Kök',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        WorkShift::query()->create([
            'user_id' => $restaurantUser->id,
            'shift_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'shift_role' => Roles::RESTAURANT,
            'shift_function' => 'kok',
            'status' => 'planned',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->from(route('admin.restaurant-functions.index'))
            ->delete(route('admin.restaurant-functions.destroy', $function))
            ->assertSessionHasErrors('restaurant_function');

        $this->assertDatabaseHas('restaurant_functions', ['id' => $function->id]);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRoles([$role]);

        return $user;
    }
}
