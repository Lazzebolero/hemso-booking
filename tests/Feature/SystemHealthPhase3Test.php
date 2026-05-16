<?php

namespace Tests\Feature;

use App\Mail\SystemHealthDailyReportMail;
use App\Mail\SystemHealthTestMail;
use App\Models\Role;
use App\Models\SystemHealthSnapshot;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SystemHealthPhase3Test extends TestCase
{
    public function test_admin_system_health_records_snapshot(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$adminRole]);

        $before = SystemHealthSnapshot::query()->count();

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.system-health.index'))
            ->assertOk()
            ->assertSee('Historik', false)
            ->assertSee('Skicka testmail', false);

        $this->assertSame($before + 1, SystemHealthSnapshot::query()->count());
    }

    public function test_admin_can_send_system_health_test_mail(): void
    {
        Mail::fake();

        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$adminRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::ADMIN])
            ->from(route('admin.system-health.index'))
            ->post(route('admin.system-health.test-mail'))
            ->assertRedirect(route('admin.system-health.index'))
            ->assertSessionHas('success');

        Mail::assertSent(SystemHealthTestMail::class, fn (SystemHealthTestMail $mail): bool => $mail->hasTo($user->email));
    }

    public function test_admin_can_fetch_status_json(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$adminRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::ADMIN])
            ->getJson(route('admin.system-health.status-json'))
            ->assertOk()
            ->assertJsonStructure([
                'overall_status',
                'checked_at',
                'checks',
                'summary' => ['ok', 'warning', 'error'],
            ]);
    }

    public function test_monitor_endpoint_requires_token(): void
    {
        config(['services.system_health.monitor_token' => 'secret-monitor-token']);

        $this->getJson(route('health.monitor'))
            ->assertForbidden();

        $this->getJson(route('health.monitor', ['token' => 'wrong']))
            ->assertForbidden();

        $response = $this->getJson(route('health.monitor', ['token' => 'secret-monitor-token']));

        $response->assertOk()
            ->assertJsonStructure(['overall_status', 'checked_at', 'checks', 'summary']);

        $this->assertContains($response->json('overall_status'), ['ok', 'warning', 'error']);
    }

    public function test_monitor_endpoint_is_disabled_without_token_config(): void
    {
        config(['services.system_health.monitor_token' => null]);

        $this->getJson(route('health.monitor', ['token' => 'anything']))
            ->assertNotFound();
    }

    public function test_daily_system_health_report_command_sends_mail_and_records_snapshot(): void
    {
        Mail::fake();
        config(['services.system_health.report_email' => 'admin@example.com']);

        $before = SystemHealthSnapshot::query()->count();

        $this->artisan('system-health:send-daily-report')
            ->assertSuccessful();

        Mail::assertSent(
            SystemHealthDailyReportMail::class,
            fn (SystemHealthDailyReportMail $mail): bool => $mail->hasTo('admin@example.com')
        );

        $this->assertSame($before + 1, SystemHealthSnapshot::query()->count());
    }
}
