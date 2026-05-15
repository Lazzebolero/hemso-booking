<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SystemHealthChecker;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function index(SystemHealthChecker $checker): View
    {
        $checks = $checker->dashboardChecks();

        return view('admin.system-health.index', [
            'checks' => $checks,
            'overallStatus' => $checker->overallStatus($checks),
            'checkedAt' => now(),
        ]);
    }
}
