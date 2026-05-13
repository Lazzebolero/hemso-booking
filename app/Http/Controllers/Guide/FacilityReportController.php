<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Models\FacilityReport;
use App\Models\ReportOption;
use App\Services\LogService;
use Illuminate\Http\Request;

class FacilityReportController extends Controller
{
    public function create()
    {
        $categories = ReportOption::where('type', 'category')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $priorities = ReportOption::where('type', 'priority')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        return view('reports.create', compact('categories', 'priorities'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $data['status'] = 'open';
        $data['reported_by'] = auth()->id();
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $report = FacilityReport::create($data);

        if (class_exists(LogService::class)) {
            LogService::log('facility_report', $report->id, 'created', null, $report->toArray(), 'Skapade felrapport');
        }

        return redirect()->route('guide.dashboard')->with('success', 'Felrapport skapad.');
    }
}
