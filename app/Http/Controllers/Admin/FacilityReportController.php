<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacilityReport;
use App\Models\FacilityReportAttachment;
use App\Models\ReportCategory;
use App\Models\ReportLocation;
use App\Models\ReportPriority;
use App\Models\ReportStatus;
use App\Models\User;
use App\Services\LogService;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class FacilityReportController extends Controller
{
    /**
     * Visa första bifogade bilden (bakåtkompatibilitet).
     */
    public function attachment(FacilityReport $report)
    {
        $first = $report->resolvedAttachments()->first();

        if ($first === null) {
            abort(404);
        }

        return $this->streamAttachmentFile($first->path);
    }

    /**
     * Visa en specifik bifogad bild.
     */
    public function showAttachment(FacilityReport $report, FacilityReportAttachment $attachment)
    {
        if ((int) $attachment->facility_report_id !== (int) $report->id) {
            abort(404);
        }

        return $this->streamAttachmentFile($attachment->path);
    }

    protected function streamAttachmentFile(?string $path)
    {
        if (empty($path) || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $absolutePath = Storage::disk('public')->path($path);

        if (! is_file($absolutePath)) {
            abort(404);
        }

        return response()->file($absolutePath);
    }

    public function index()
    {
        $user = auth()->user();
        if ($user instanceof User && session('active_role') === Roles::ADMIN) {
            if (Schema::hasColumn('users', 'facility_reports_acknowledged_at')) {
                $user->forceFill(['facility_reports_acknowledged_at' => now()])->saveQuietly();
            }
        }

        $reports = FacilityReport::with([
            'category',
            'priority',
            'statusRelation',
            'location',
            'reporter',
            'assignee',
        ])
            ->latest()
            ->paginate(20);

        return view('admin.reports.index', compact('reports'));
    }

    public function show(FacilityReport $report)
    {
        $report->load([
            'category',
            'priority',
            'statusRelation',
            'location',
            'reporter',
            'assignee',
            'attachments',
        ]);

        return view('admin.reports.show', compact('report'));
    }

    public function edit(FacilityReport $report)
    {
        $categories = ReportCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $priorities = ReportPriority::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $statuses = ReportStatus::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $locations = ReportLocation::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $users = User::orderBy('name')->get();

        return view('admin.reports.edit', compact(
            'report',
            'categories',
            'priorities',
            'statuses',
            'locations',
            'users'
        ));
    }

    public function update(Request $request, FacilityReport $report)
    {
        $old = $report->toArray();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category_id' => ['required', 'exists:report_categories,id'],
            'priority_id' => ['required', 'exists:report_priorities,id'],
            'status_id' => ['required', 'exists:report_statuses,id'],
            'location_id' => ['nullable', 'exists:report_locations,id'],
            'location_text' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $report->update($data);

        if (class_exists(LogService::class)) {
            LogService::log(
                'facility_report',
                $report->id,
                'updated',
                $old,
                $report->fresh()->toArray(),
                'Uppdaterade felrapport från admin'
            );
        }

        return redirect()
            ->route('admin.reports.show', $report)
            ->with('success', 'Felrapport uppdaterad.');
    }
}
