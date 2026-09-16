<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SystemMessage;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class SystemMessagePopupTest extends TestCase
{
    public function test_live_panel_returns_unread_messages(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $message = SystemMessage::query()->create([
            'title' => 'Viktigt driftmeddelande',
            'body' => 'Kontrollera bokningssystemet.',
            'message_type' => 'message',
            'target_roles' => ['admin'],
            'is_important' => true,
            'priority' => 3,
            'popup_only' => false,
            'requires_ack' => true,
            'send_email' => false,
            'is_active' => true,
            'starts_at' => now()->subMinute(),
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->getJson(route('system-messages.live-panel'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('unread.0.id', $message->id)
            ->assertJsonPath('important_unread.0.id', $message->id)
            ->assertJsonPath('important_unread.0.title', 'Viktigt driftmeddelande')
            ->assertJsonPath('important_unread.0.requires_ack', true);
    }

    public function test_force_popup_panel_includes_normal_unread_message(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $message = SystemMessage::query()->create([
            'title' => 'Enkel påminnelse',
            'body' => 'Glöm inte att läsa detta.',
            'message_type' => 'message',
            'target_roles' => ['admin'],
            'is_important' => false,
            'priority' => 2,
            'popup_only' => false,
            'requires_ack' => false,
            'send_email' => false,
            'is_active' => true,
            'starts_at' => now()->subMinute(),
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->getJson(route('system-messages.force-popup-panel'))
            ->assertOk()
            ->assertJsonPath('messages.0.id', $message->id);
    }

    public function test_popup_only_message_is_visible_in_admin_dashboard_banner(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        SystemMessage::query()->create([
            'title' => 'Endast popup',
            'body' => 'Detta ska synas i panelen.',
            'message_type' => 'message',
            'target_roles' => ['admin'],
            'is_important' => false,
            'priority' => 2,
            'popup_only' => true,
            'requires_ack' => false,
            'send_email' => false,
            'is_active' => true,
            'starts_at' => now()->subMinute(),
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Endast popup', false)
            ->assertSee('system-messages-panel', false)
            ->assertSee('loadForcedPopups', false)
            ->assertSee('showForcedSystemModal', false)
            ->assertSee("const tag = 'd' + 'iv'", false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
