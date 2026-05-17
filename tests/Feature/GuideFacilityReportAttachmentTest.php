<?php

namespace Tests\Feature;

use App\Models\FacilityReport;
use App\Models\ReportCategory;
use App\Models\ReportPriority;
use App\Models\ReportStatus;
use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuideFacilityReportAttachmentTest extends TestCase
{
    public function test_guide_facility_report_form_uses_stable_photo_picker(): void
    {
        $user = User::factory()->create();
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $user->assignRoles([$guideRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.reports.create'))
            ->assertOk()
            ->assertSee('name="attachment"', false)
            ->assertSee('accept="image/jpeg,image/png,image/gif,image/webp"', false)
            ->assertDontSee('capture="environment"', false)
            ->assertSee('Ta bilden med kameraappen först', false);
    }

    public function test_guide_can_submit_facility_report_with_optional_image(): void
    {
        Mail::fake();
        Storage::fake('public');

        $user = User::factory()->create();
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $user->assignRoles([$guideRole]);

        $category = ReportCategory::query()->create([
            'name' => 'Testkategori',
            'code' => 'test_cat',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $priority = ReportPriority::query()->create([
            'name' => 'Normal',
            'code' => 'test_pri',
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

        $file = UploadedFile::fake()->image('plats.jpg', 120, 120);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('guide.reports.store'), [
                'title' => 'Trasig lampa',
                'description' => 'Lampan blinkar i sal 2.',
                'category_id' => $category->id,
                'priority_id' => $priority->id,
                'attachment' => $file,
            ])
            ->assertRedirect(route('guide.dashboard'))
            ->assertSessionHas('success');

        $report = FacilityReport::query()->where('title', 'Trasig lampa')->first();
        $this->assertNotNull($report);
        $this->assertNotEmpty($report->attachment_path);
        Storage::disk('public')->assertExists($report->attachment_path);
    }
}
