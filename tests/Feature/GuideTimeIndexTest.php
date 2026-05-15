<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TimeEntry;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuideTimeIndexTest extends TestCase
{
    public function test_guide_can_open_time_index(): void
    {
        if (! Schema::hasTable('time_entries')) {
            $this->markTestSkipped('time_entries-tabellen saknas.');
        }

        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$guideRole]);

        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'status' => TimeEntry::STATUS_DRAFT,
        ]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('time.index'))
            ->assertOk()
            ->assertSee('Tidrapportering', false);
    }
}
