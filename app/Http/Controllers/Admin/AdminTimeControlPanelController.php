<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayrollPeriodFilterRequest;
use App\Models\TimeEntry;
use App\Services\PayrollPeriodService;
use App\Services\TimeEntryDeviationService;
use Illuminate\View\View;

class AdminTimeControlPanelController extends Controller
{
    public function index(PayrollPeriodFilterRequest $request): View
    {
        $period = PayrollPeriodService::resolveFromRequest($request->payrollPeriodQuery());

        $entries = TimeEntry::query()
            ->with(['user', 'audits'])
            ->withCount('audits')
            ->whereBetween('work_date', [$period['start_date'], $period['end_date']])
            ->orderByDesc('work_date')
            ->orderByDesc('start_at')
            ->get();

        $entries = TimeEntryDeviationService::appendOverlapDeviations($entries);

        $openEntries = $entries->where('status', TimeEntry::STATUS_OPEN)->values();
        $submittedEntries = $entries->where('status', TimeEntry::STATUS_SUBMITTED)->values();
        $correctedEntries = $entries->where('status', TimeEntry::STATUS_CORRECTED)->values();
        $approvedEntries = $entries->where('status', TimeEntry::STATUS_APPROVED)->values();

        $unapprovedEntries = $entries
            ->filter(fn (TimeEntry $entry) => $entry->status !== TimeEntry::STATUS_APPROVED)
            ->values();

        $deviationEntries = $entries
            ->filter(fn (TimeEntry $entry) => count($entry->deviations ?? []) > 0)
            ->values();

        $selectedView = $request->query('view', 'problems');

        $visibleEntries = match ($selectedView) {
            'open' => $openEntries,
            'unapproved' => $unapprovedEntries,
            'deviations' => $deviationEntries,
            'submitted' => $submittedEntries,
            'corrected' => $correctedEntries,
            'all' => $entries,
            default => $entries
                ->filter(fn (TimeEntry $entry) => $entry->status === TimeEntry::STATUS_OPEN ||
                    $entry->status === TimeEntry::STATUS_SUBMITTED ||
                    $entry->status === TimeEntry::STATUS_CORRECTED ||
                    count($entry->deviations ?? []) > 0
                )
                ->values(),
        };

        $totalMinutes = $entries->sum(fn (TimeEntry $entry) => $entry->worked_minutes);
        $approvedMinutes = $approvedEntries->sum(fn (TimeEntry $entry) => $entry->worked_minutes);

        $blockingDeviations = $deviationEntries->filter(function (TimeEntry $entry) {
            return collect($entry->deviations ?? [])
                ->whereIn('severity', ['danger', 'warning'])
                ->isNotEmpty();
        });

        $periodReadyForExport = $entries->isNotEmpty()
            && $openEntries->isEmpty()
            && $submittedEntries->isEmpty()
            && $correctedEntries->isEmpty()
            && $blockingDeviations->isEmpty();

        return view('admin.time.control-panel', [
            'period' => $period,
            'visibleEntries' => $visibleEntries,
            'selectedView' => $selectedView,
            'openCount' => $openEntries->count(),
            'unapprovedCount' => $unapprovedEntries->count(),
            'submittedCount' => $submittedEntries->count(),
            'correctedCount' => $correctedEntries->count(),
            'deviationCount' => $deviationEntries->count(),
            'totalFormatted' => sprintf('%dh %02dm', intdiv($totalMinutes, 60), $totalMinutes % 60),
            'approvedFormatted' => sprintf('%dh %02dm', intdiv($approvedMinutes, 60), $approvedMinutes % 60),
            'periodReadyForExport' => $periodReadyForExport,
        ]);
    }
}
