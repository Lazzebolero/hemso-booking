<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserUpdateTest extends TestCase
{
    public function test_admin_can_update_user_without_changing_password(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();

        $user = User::factory()->create([
            'name' => 'Ursprungligt namn',
            'email' => 'ursprunglig@example.com',
            'password' => Hash::make('befintligt-losenord'),
        ]);
        $user->assignRoles([$guideRole]);

        $originalPasswordHash = $user->password;

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.users.update', $user), [
                'name' => 'Uppdaterat namn',
                'email' => 'uppdaterad@example.com',
                'phone' => '0701112233',
                'roles' => [Roles::GUIDE],
                'is_active' => '1',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('Uppdaterat namn', $user->name);
        $this->assertSame('uppdaterad@example.com', $user->email);
        $this->assertSame('0701112233', $user->phone);
        $this->assertSame($originalPasswordHash, $user->password);
    }

    public function test_admin_must_confirm_password_when_changing_it_on_update(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();

        $user = User::factory()->create([
            'password' => Hash::make('befintligt-losenord'),
        ]);
        $user->assignRoles([$guideRole]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->from(route('admin.users.edit', $user))
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => [Roles::GUIDE],
                'is_active' => '1',
                'password' => 'nytt-losenord',
                'password_confirmation' => 'fel-bekraftelse',
            ])
            ->assertRedirect(route('admin.users.edit', $user))
            ->assertSessionHasErrors('password');
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
