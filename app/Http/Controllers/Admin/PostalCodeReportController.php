<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PostalCodeReportService;
use App\Support\ActiveRole;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostalCodeReportController extends Controller
{
    public function __construct(
        private PostalCodeReportService $reportService,
    ) {}

    public function index(Request $request): View
    {
        $year = $request->integer('year') ?: null;
        $report = $this->reportService->build($year);

        return view('admin.postal-codes.report', [
            'report' => $report,
            'year' => $report['year'],
            'availableYears' => $report['available_years'],
            'prefix' => ActiveRole::routePrefix(),
        ]);
    }
}
