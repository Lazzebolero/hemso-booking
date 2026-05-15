<?php

namespace Tests\Feature;

use App\Mail\NewFacilityReportMail;
use App\Models\FacilityReport;
use App\Models\ReportCategory;
use App\Models\ReportPriority;
use App\Models\ReportStatus;
use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FacilityReportAdminNotificationTest extends TestCase
{
    public function test_sends_mail_to_each_active_admin_when_guide_creates_report(): void
    {
        Mail::fake();

        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();

        $adminOne = User::factory()->create(['is_active' => true]);
        $adminOne->assignRoles([$adminRole]);

        $adminTwo = User::factory()->create(['is_active' => true]);
        $adminTwo->assignRoles([$adminRole]);

        $inactiveAdmin = User::factory()->create(['is_active' => false]);
        $inactiveAdmin->assignRoles([$adminRole]);

        $category = ReportCategory::query()->create([
            'name' => 'Testkategori',
            'code' => 'test_cat_mail',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $priority = ReportPriority::query()->create([
            'name' => 'Normal',
            'code' => 'test_pri_mail',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ReportStatus::query()->firstOrCreate(
            ['code' => 'open'],
            [
                'name' => 'Öppen',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $guide = User::factory()->create();
        $guide->assignRoles([$guideRole]);

        $expectedAdminMailCount = User::query()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->whereHas('roles', function ($query): void {
                $query->where('slug', Roles::ADMIN);
            })
            ->count();

        $this->assertGreaterThanOrEqual(2, $expectedAdminMailCount);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('guide.reports.store'), [
                'title' => 'Mailtest felrapport',
                'description' => 'Beskrivning för mailtest.',
                'category_id' => $category->id,
                'priority_id' => $priority->id,
            ])
            ->assertRedirect(route('guide.dashboard'));

        Mail::assertSent(NewFacilityReportMail::class, $expectedAdminMailCount);

        $this->assertSame(
            1,
            Mail::sent(NewFacilityReportMail::class, fn (NewFacilityReportMail $mail) => $mail->hasTo($adminOne->email))->count()
        );
        $this->assertSame(
            1,
            Mail::sent(NewFacilityReportMail::class, fn (NewFacilityReportMail $mail) => $mail->hasTo($adminTwo->email))->count()
        );

        Mail::assertSent(NewFacilityReportMail::class, function (NewFacilityReportMail $mail) use ($adminOne): bool {
            return $mail->hasTo($adminOne->email);
        });

        Mail::assertSent(NewFacilityReportMail::class, function (NewFacilityReportMail $mail) use ($adminTwo): bool {
            return $mail->hasTo($adminTwo->email);
        });

        Mail::assertNotSent(NewFacilityReportMail::class, $inactiveAdmin->email);
    }

    public function test_does_not_send_new_facility_report_mail_to_host_role_only(): void
    {
        Mail::fake();

        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();

        $hostOnly = User::factory()->create(['is_active' => true]);
        $hostOnly->assignRoles([$hostRole]);

        $category = ReportCategory::query()->create([
            'name' => 'Kategori host-mail',
            'code' => 'test_cat_host_mail',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $priority = ReportPriority::query()->create([
            'name' => 'Normal',
            'code' => 'test_pri_host_mail',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ReportStatus::query()->firstOrCreate(
            ['code' => 'open'],
            [
                'name' => 'Öppen',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $guide = User::factory()->create();
        $guide->assignRoles([$guideRole]);

        $adminForInclusion = User::factory()->create(['is_active' => true]);
        $adminForInclusion->assignRoles([$adminRole]);

        $expectedAdminMailCount = User::query()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->whereHas('roles', function ($query): void {
                $query->where('slug', Roles::ADMIN);
            })
            ->count();

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('guide.reports.store'), [
                'title' => 'Rapport utan värd-mail',
                'description' => 'Bara admin ska få e-post.',
                'category_id' => $category->id,
                'priority_id' => $priority->id,
            ])
            ->assertRedirect(route('guide.dashboard'));

        Mail::assertNotSent(NewFacilityReportMail::class, $hostOnly->email);
        Mail::assertSent(NewFacilityReportMail::class, $expectedAdminMailCount);
        Mail::assertSent(NewFacilityReportMail::class, fn (NewFacilityReportMail $mail) => $mail->hasTo($adminForInclusion->email));
    }

    public function test_dashboard_shows_new_facility_reports_notice_until_reports_index_is_opened(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();

        $admin = User::factory()->create();
        $admin->assignRoles([$adminRole]);
        $this->assertNull($admin->facility_reports_acknowledged_at);

        $guide = User::factory()->create();
        $guide->assignRoles([$guideRole]);

        $category = ReportCategory::query()->create([
            'name' => 'Kat dash',
            'code' => 'test_cat_dash',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $priority = ReportPriority::query()->create([
            'name' => 'Låg',
            'code' => 'test_pri_dash',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $open = ReportStatus::query()->firstOrCreate(
            ['code' => 'open'],
            [
                'name' => 'Öppen',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        FacilityReport::query()->create([
            'title' => 'Befintlig rapport',
            'description' => 'Text',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $open->id,
            'location_id' => null,
            'location_text' => null,
            'reported_by' => $guide->id,
            'assigned_to' => null,
            'attachment_path' => null,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Nya felrapporter', false)
            ->assertSee('data-facility-reports-nav-badge="', false);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.reports.index'))
            ->assertOk();

        $admin->refresh();
        $this->assertNotNull($admin->facility_reports_acknowledged_at);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Nya felrapporter', false)
            ->assertDontSee('data-facility-reports-nav-badge', false);
    }

    public function test_host_dashboard_does_not_show_new_facility_reports_notice(): void
    {
        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();

        $host = User::factory()->create();
        $host->assignRoles([$hostRole]);

        $guide = User::factory()->create();
        $guide->assignRoles([$guideRole]);

        $category = ReportCategory::query()->create([
            'name' => 'Kat host dash',
            'code' => 'test_cat_host_dash',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $priority = ReportPriority::query()->create([
            'name' => 'Låg',
            'code' => 'test_pri_host_dash',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $open = ReportStatus::query()->firstOrCreate(
            ['code' => 'open'],
            [
                'name' => 'Öppen',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        FacilityReport::query()->create([
            'title' => 'Rapport värd ska inte se',
            'description' => 'Text',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $open->id,
            'location_id' => null,
            'location_text' => null,
            'reported_by' => $guide->id,
            'assigned_to' => null,
            'attachment_path' => null,
        ]);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.dashboard'))
            ->assertOk()
            ->assertDontSee('Nya felrapporter', false)
            ->assertDontSee('data-facility-reports-nav-badge', false);
    }

    public function test_dashboard_notice_returns_after_new_open_report_after_acknowledgment(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();

        $admin = User::factory()->create();
        $admin->assignRoles([$adminRole]);

        $guide = User::factory()->create();
        $guide->assignRoles([$guideRole]);

        $category = ReportCategory::query()->create([
            'name' => 'Kat dash 2',
            'code' => 'test_cat_dash2',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $priority = ReportPriority::query()->create([
            'name' => 'Låg',
            'code' => 'test_pri_dash2',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $open = ReportStatus::query()->firstOrCreate(
            ['code' => 'open'],
            [
                'name' => 'Öppen',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        FacilityReport::query()->create([
            'title' => 'Gammal',
            'description' => 'Text',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $open->id,
            'location_id' => null,
            'location_text' => null,
            'reported_by' => $guide->id,
            'assigned_to' => null,
            'attachment_path' => null,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.reports.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Nya felrapporter', false)
            ->assertDontSee('data-facility-reports-nav-badge', false);

        $this->travel(2)->seconds();

        FacilityReport::query()->create([
            'title' => 'Ny efter ack',
            'description' => 'Text',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $open->id,
            'location_id' => null,
            'location_text' => null,
            'reported_by' => $guide->id,
            'assigned_to' => null,
            'attachment_path' => null,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Nya felrapporter', false)
            ->assertSee('data-facility-reports-nav-badge="', false);
    }

    public function test_admin_sidebar_shows_nav_badge_on_bookings_page_when_new_reports_exist(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();

        $admin = User::factory()->create();
        $admin->assignRoles([$adminRole]);

        $guide = User::factory()->create();
        $guide->assignRoles([$guideRole]);

        $category = ReportCategory::query()->create([
            'name' => 'Kat sidebar',
            'code' => 'test_cat_sidebar',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $priority = ReportPriority::query()->create([
            'name' => 'Låg',
            'code' => 'test_pri_sidebar',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $open = ReportStatus::query()->firstOrCreate(
            ['code' => 'open'],
            [
                'name' => 'Öppen',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        FacilityReport::query()->create([
            'title' => 'Sidebar-test',
            'description' => 'Text',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $open->id,
            'location_id' => null,
            'location_text' => null,
            'reported_by' => $guide->id,
            'assigned_to' => null,
            'attachment_path' => null,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertSee('data-facility-reports-nav-badge="', false);
    }
}
