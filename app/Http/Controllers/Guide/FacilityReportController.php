<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Models\FacilityReport;
use App\Models\ReportCategory;
use App\Models\ReportLocation;
use App\Models\ReportPriority;
use App\Models\ReportStatus;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;

class FacilityReportController extends Controller
{
    public function create()
    {
        $categories = ReportCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $priorities = ReportPriority::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $locations = ReportLocation::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('guide.report-form', compact('categories', 'priorities', 'locations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category_id' => ['required', 'exists:report_categories,id'],
            'priority_id' => ['required', 'exists:report_priorities,id'],
            'location_id' => ['nullable', 'exists:report_locations,id'],
            'location_text' => ['nullable', 'string', 'max:255'],
            'attachment' => [
                'nullable',
                File::types(['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif'])
                    ->max(10240),
            ],
        ], [
            'title.required' => 'Ange en rubrik.',
            'description.required' => 'Ange en beskrivning.',
            'category_id.required' => 'Välj kategori.',
            'priority_id.required' => 'Välj klassning.',
            'attachment.max' => 'Bilden får vara högst 10 MB.',
        ]);

        $openStatus = ReportStatus::where('code', 'open')->firstOrFail();

        $attachmentPath = null;
        $uploaded = $request->file('attachment');
        if ($uploaded instanceof UploadedFile && $uploaded->isValid()) {
            $stored = $uploaded->store(
                'facility_reports/'.now()->format('Y/m'),
                'public'
            );
            $attachmentPath = $stored !== false ? $stored : null;
        }

        $report = FacilityReport::create([
            'title' => $request->string('title')->toString(),
            'description' => $request->string('description')->toString(),
            'category_id' => $request->integer('category_id'),
            'priority_id' => $request->integer('priority_id'),
            'location_id' => $request->filled('location_id') ? $request->integer('location_id') : null,
            'location_text' => $request->input('location_text'),
            'status_id' => $openStatus->id,
            'reported_by' => $request->user()->id,
            'assigned_to' => null,
            'attachment_path' => $attachmentPath,
        ]);

        if (class_exists(LogService::class)) {
            LogService::log(
                'facility_report',
                $report->id,
                'created',
                null,
                $report->toArray(),
                'Skapade felrapport från guidevy'
            );
        }

        return redirect()
            ->route('guide.dashboard')
            ->with('success', 'Felrapport skapad.');
    }
}