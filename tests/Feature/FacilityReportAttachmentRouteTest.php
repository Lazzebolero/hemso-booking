<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FacilityReportAttachmentRouteTest extends TestCase
{
    public function test_admin_and_host_define_facility_report_attachment_route(): void
    {
        $this->assertTrue(Route::has('admin.reports.attachment'));
        $this->assertTrue(Route::has('host.reports.attachment'));
    }
}
