<?php

namespace Tests\Feature;

use App\Mail\DailySystemHealthReportMail;
use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DailySystemHealthReportTest extends TestCase
{
    public function test_daily_report_command_sends_email_when_configured(): void
    {
        Mail::fake();

        config([
            'services.system_health.report_email' => 'drift@example.test',
            'app.url' => 'https://bokning.example.test',
        ]);

        $this->artisan('system-health:send-daily-report')
            ->assertSuccessful()
            ->expectsOutputToContain('drift@example.test');

        Mail::assertSent(DailySystemHealthReportMail::class, function (DailySystemHealthReportMail $mail) {
            return $mail->hasTo('drift@example.test')
                && $mail->report['overall_status'] !== ''
                && count($mail->report['checks']) >= 10;
        });
    }

    public function test_daily_report_command_skips_when_email_missing(): void
    {
        Mail::fake();

        config([
            'services.system_health.report_email' => null,
        ]);

        $this->artisan('system-health:send-daily-report')
            ->assertSuccessful()
            ->expectsOutputToContain('SYSTEM_HEALTH_REPORT_EMAIL saknas');

        Mail::assertNothingSent();
    }

    public function test_admin_can_view_system_health_page_with_report_checks(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $admin = User::factory()->create();
        $admin->assignRoles([$adminRole]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.system-health.index'))
            ->assertOk()
            ->assertSee('Systemhälsa', false)
            ->assertSee('Migrationer', false)
            ->assertSee('Jobbkö', false);
    }
}
