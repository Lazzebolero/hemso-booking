<?php

namespace App\Http\Controllers\Guide;

use App\Events\FacilityReportCreated;
use App\Http\Controllers\Controller;
use App\Models\FacilityReport;
use App\Models\FacilityReportAttachment;
use App\Models\ReportCategory;
use App\Models\ReportLocation;
use App\Models\ReportPriority;
use App\Models\ReportStatus;
use App\Services\FacilityReportNotificationService;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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

        $maxAttachments = FacilityReportNotificationService::MAX_ATTACHMENTS;

        return view('guide.report-form', compact('categories', 'priorities', 'locations', 'maxAttachments'));
    }

    public function store(Request $request)
    {
        $maxAttachments = FacilityReportNotificationService::MAX_ATTACHMENTS;

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category_id' => ['required', 'exists:report_categories,id'],
            'priority_id' => ['required', 'exists:report_priorities,id'],
            'location_id' => ['nullable', 'exists:report_locations,id'],
            'location_text' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable', 'array', 'max:'.$maxAttachments],
            'attachments.*' => [
                'nullable',
                File::types(['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif'])
                    ->max(10240),
            ],
            // Bakåtkompatibilitet om äldre klient fortfarande skickar ett enskilt fält.
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
            'attachments.max' => 'Du kan bifoga högst '.$maxAttachments.' bilder.',
            'attachments.*.max' => 'Varje bild får vara högst 10 MB.',
            'attachment.max' => 'Bilden får vara högst 10 MB.',
        ]);

        $openStatus = ReportStatus::where('code', 'open')->firstOrFail();

        $uploadedFiles = collect($request->file('attachments', []))
            ->filter(fn ($file) => $file instanceof UploadedFile && $file->isValid())
            ->values();

        $legacy = $request->file('attachment');
        if ($uploadedFiles->isEmpty() && $legacy instanceof UploadedFile && $legacy->isValid()) {
            $uploadedFiles = collect([$legacy]);
        }

        $report = DB::transaction(function () use ($request, $openStatus, $uploadedFiles) {
            $storedPaths = [];

            foreach ($uploadedFiles as $uploaded) {
                $stored = $uploaded->store(
                    'facility_reports/'.now()->format('Y/m'),
                    'public'
                );

                if ($stored !== false) {
                    $storedPaths[] = [
                        'path' => $stored,
                        'original_name' => $uploaded->getClientOriginalName(),
                    ];
                }
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
                'attachment_path' => $storedPaths[0]['path'] ?? null,
            ]);

            foreach ($storedPaths as $index => $storedPath) {
                FacilityReportAttachment::query()->create([
                    'facility_report_id' => $report->id,
                    'path' => $storedPath['path'],
                    'original_name' => $storedPath['original_name'],
                    'sort_order' => $index,
                ]);
            }

            return $report;
        });

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

        FacilityReportCreated::dispatch($report->load('attachments'));

        return redirect()
            ->route('guide.dashboard')
            ->with('success', 'Felrapport skapad.');
    }
}
