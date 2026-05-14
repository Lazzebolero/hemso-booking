<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayrollPeriodFilterRequest;
use App\Models\TimeEntry;
use App\Models\TimeEntryAudit;
use App\Models\User;
use App\Services\PayrollPeriodService;
use App\Services\TimeEntryDeviationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTimeEntryController extends Controller
{
    public function index(PayrollPeriodFilterRequest $request): View
    {
        $period = PayrollPeriodService::resolveFromRequest($request->payrollPeriodQuery());

        $query = TimeEntry::query()
            ->with(['user', 'audits'])
            ->withCount('audits')
            ->whereBetween('work_date', [$period['start_date'], $period['end_date']])
            ->orderByDesc('work_date')
            ->orderByDesc('start_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $entries = $query->paginate(30)->withQueryString();

        $entries->setCollection(
            TimeEntryDeviationService::appendOverlapDeviations($entries->getCollection())
        );

        $summaryEntries = TimeEntry::query()
            ->with(['user', 'audits'])
            ->whereBetween('work_date', [$period['start_date'], $period['end_date']])
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->input('user_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->get();

        $summaryEntries = TimeEntryDeviationService::appendOverlapDeviations($summaryEntries);

        $summary = $summaryEntries
            ->groupBy('user_id')
            ->map(function ($rows) {
                $first = $rows->first();
                $minutes = $rows->sum(fn (TimeEntry $entry) => $entry->worked_minutes);
                $deviationCount = $rows->sum(fn (TimeEntry $entry) => count($entry->deviations ?? []));

                return [
                    'user' => $first->user,
                    'passes' => $rows->count(),
                    'minutes' => $minutes,
                    'formatted' => sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60),
                    'open' => $rows->where('status', TimeEntry::STATUS_OPEN)->count(),
                    'draft' => $rows->where('status', TimeEntry::STATUS_DRAFT)->count(),
                    'submitted' => $rows->where('status', TimeEntry::STATUS_SUBMITTED)->count(),
                    'corrected' => $rows->where('status', TimeEntry::STATUS_CORRECTED)->count(),
                    'approved' => $rows->where('status', TimeEntry::STATUS_APPROVED)->count(),
                    'deviations' => $deviationCount,
                ];
            })
            ->values();

        $totalMinutes = $summaryEntries->sum(fn (TimeEntry $entry) => $entry->worked_minutes);
        $totalDeviations = $summaryEntries->sum(fn (TimeEntry $entry) => count($entry->deviations ?? []));

        return view('admin.time.index', [
            'entries' => $entries,
            'users' => User::query()->orderBy('name')->get(),
            'period' => $period,
            'summary' => $summary,
            'totalMinutes' => $totalMinutes,
            'totalFormatted' => sprintf('%dh %02dm', intdiv($totalMinutes, 60), $totalMinutes % 60),
            'totalDeviations' => $totalDeviations,
        ]);
    }

    public function show(TimeEntry $timeEntry): View
    {
        $timeEntry->load([
            'user',
            'audits.changedBy',
        ]);

        $deviations = TimeEntryDeviationService::forEntry($timeEntry);

        return view('admin.time.show', [
            'entry' => $timeEntry,
            'deviations' => $deviations,
        ]);
    }

    public function approve(Request $request, TimeEntry $timeEntry): RedirectResponse
    {
        TimeEntryAudit::create([
            'time_entry_id' => $timeEntry->id,
            'changed_by' => $request->user()->id,
            'field' => 'status',
            'old_value' => $timeEntry->status,
            'new_value' => TimeEntry::STATUS_APPROVED,
            'source' => 'admin',
            'reason' => 'Godkänd av admin.',
        ]);

        $timeEntry->update([
            'status' => TimeEntry::STATUS_APPROVED,
        ]);

        return back()->with('success', 'Tiden godkänd.');
    }

    public function correct(Request $request, TimeEntry $timeEntry): RedirectResponse
    {
        $validated = $request->validate([
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'admin_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['break_minutes'] = (int) ($validated['break_minutes'] ?? 0);

        foreach (['start_at', 'end_at', 'break_minutes', 'admin_comment'] as $field) {
            $oldValue = $timeEntry->{$field};
            $newValue = $validated[$field] ?? null;

            $oldNormalized = $oldValue instanceof \DateTimeInterface
                ? $oldValue->format('Y-m-d H:i:s')
                : (string) ($oldValue ?? '');

            $newNormalized = $newValue instanceof \DateTimeInterface
                ? $newValue->format('Y-m-d H:i:s')
                : (string) ($newValue ?? '');

            if ($oldNormalized === $newNormalized) {
                continue;
            }

            TimeEntryAudit::create([
                'time_entry_id' => $timeEntry->id,
                'changed_by' => $request->user()->id,
                'field' => $field,
                'old_value' => $oldNormalized,
                'new_value' => $newNormalized,
                'source' => 'admin',
                'reason' => 'Korrigerad av admin.',
            ]);
        }

        $timeEntry->update([
            'start_at' => $validated['start_at'],
            'end_at' => $validated['end_at'] ?? null,
            'break_minutes' => $validated['break_minutes'],
            'admin_comment' => $validated['admin_comment'] ?? null,
            'work_date' => Carbon::parse($validated['start_at'])->toDateString(),
            'status' => TimeEntry::STATUS_CORRECTED,
        ]);

        return back()->with('success', 'Tiden korrigerad.');
    }
}
