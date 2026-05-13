<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Models\FacilityReport;
use App\Services\LogService;
use Illuminate\Http\Request;

class FacilityReportController extends Controller
{
    public function create()
    {
        return view('guide.report-form');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:building,electricity,security,cleaning,equipment,other',
            'priority' => 'required|in:low,normal,high,urgent',
            'location' => 'nullable|string|max:255',
        ]);
        $data['reported_by'] = auth()->id();
        $report = FacilityReport::create($data);
        LogService::log('facility_report', $report->id, 'created', null, $report->toArray(), 'Guide skapade felrapport');
        return redirect()->route('guide.dashboard')->with('success', 'Felrapport skapad.');
    }
}
