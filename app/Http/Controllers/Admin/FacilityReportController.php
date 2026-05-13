<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacilityReport;
use App\Models\ReportOption;
use App\Services\LogService;
use Illuminate\Http\Request;

class FacilityReportController extends Controller
{
    public function index()
    {
        $reports = FacilityReport::latest()->paginate(20);
        return view('admin.reports.index', compact('reports'));
    }

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
            'status' => ['nullable', 'in:open,in_progress,resolved,closed'],
        ]);

        $data['status'] = $data['status'] ?? 'open';
        $data['reported_by'] = auth()->id();
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $report = FacilityReport::create($data);

        if (class_exists(LogService::class)) {
            LogService::log('facility_report', $report->id, 'created', null, $report->toArray(), 'Skapade felrapport');
        }

        return redirect()->route('admin.reports.index')->with('success', 'Felrapport skapad.');
    }

    public function show(FacilityReport $report)
    {
        return view('admin.reports.show', compact('report'));
    }

    public function edit(FacilityReport $report)
    {
        $categories = ReportOption::where('type', 'category')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $priorities = ReportOption::where('type', 'priority')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.reports.edit', compact('report', 'categories', 'priorities'));
    }

    public function update(Request $request, FacilityReport $report)
    {
        $old = $report->toArray();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
        ]);

        $data['updated_by'] = auth()->id();
        $report->update($data);

        if (class_exists(LogService::class)) {
            LogService::log('facility_report', $report->id, 'updated', $old, $report->fresh()->toArray(), 'Uppdaterade felrapport');
        }

        return redirect()->route('admin.reports.index')->with('success', 'Felrapport uppdaterad.');
    }

    public function destroy(FacilityReport $report)
    {
        $old = $report->toArray();
        $report->delete();

        if (class_exists(LogService::class)) {
            LogService::log('facility_report', $report->id, 'deleted', $old, null, 'Tog bort felrapport');
        }

        return back()->with('success', 'Felrapport borttagen.');
    }
}
