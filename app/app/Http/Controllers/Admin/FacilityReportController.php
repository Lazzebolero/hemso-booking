<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacilityReport;
use App\Models\User;
use App\Services\LogService;
use Illuminate\Http\Request;

class FacilityReportController extends Controller
{
    public function index()
    {
        $reports = FacilityReport::with('reporter', 'assignee')->latest()->paginate(20);
        return view('admin.reports.index', compact('reports'));
    }

    public function edit(FacilityReport $report)
    {
        $users = User::orderBy('name')->get();
        return view('admin.reports.form', compact('report', 'users'));
    }

    public function update(Request $request, FacilityReport $report)
    {
        $old = $report->toArray();
        $data = $request->validate([
            'status' => 'required|in:new,in_progress,waiting_action,resolved,closed',
            'assigned_to' => 'nullable|exists:users,id',
            'internal_comment' => 'nullable|string',
        ]);
        if (in_array($data['status'], ['resolved', 'closed'], true) && !$report->resolved_at) {
            $data['resolved_at'] = now();
        }
        $report->update($data);
        LogService::log('facility_report', $report->id, 'updated', $old, $report->fresh()->toArray(), 'Uppdaterade felrapport');
        return redirect()->route('admin.reports.index')->with('success', 'Felrapport uppdaterad.');
    }

    public function destroy(FacilityReport $report)
    {
        $old = $report->toArray();
        $report->delete();
        LogService::log('facility_report', $report->id, 'deleted', $old, null, 'Tog bort felrapport');
        return back()->with('success', 'Felrapport borttagen.');
    }
}
