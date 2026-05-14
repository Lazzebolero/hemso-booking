<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FacilityReportAttachmentRouteTest extends TestCase
{
    public function test_admin_defines_facility_report_attachment_route(): void
    {
        $this->assertTrue(Route::has('admin.reports.attachment'));
        $this->assertFalse(Route::has('host.reports.attachment'));
    }
}
