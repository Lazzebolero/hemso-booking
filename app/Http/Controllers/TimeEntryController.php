<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Models\TimeEntryAudit;
use App\Services\PayrollLockService;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class TimeEntryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', TimeEntry::class);

        $filter = $request->query('filter', 'week');
        [$from, $to] = TimeEntry::filterPeriod($filter);

        $entries = TimeEntry::query()
            ->forUser($request->user()->id)
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->withCount('audits')
            ->orderByDesc('work_date')
            ->orderByDesc('start_at')
            ->get();

        $openEntry = TimeEntry::currentOpenForUser($request->user()->id);
        $openEntries = TimeEntry::openEntriesForUser($request->user()->id);
        $totalMinutes = $entries->sum(fn (TimeEntry $entry) => $entry->worked_minutes);

        $activeRole = session('active_role', $request->user()->role);

        $useGuideLayout = $activeRole === Roles::GUIDE;

        return view('time.index', [
            'entries' => $entries,
            'openEntry' => $openEntry,
            'openEntries' => $openEntries,
            'filter' => $filter,
            'from' => $from,
            'to' => $to,
            'totalMinutes' => $totalMinutes,
            'totalFormatted' => sprintf('%dh %02dm', intdiv($totalMinutes, 60), $totalMinutes % 60),
            'activeRole' => $activeRole,
            'useGuideLayout' => $useGuideLayout,
        ]);
    }

    public function edit(Request $request, TimeEntry $timeEntry): View
    {
        $this->authorize('update', $timeEntry);
        PayrollLockService::assertWorkDateUnlockedForUser($timeEntry->work_date->format('Y-m-d'));

        $timeEntry->load('audits.changedBy');

        $activeRole = session('active_role', $request->user()->role);

        return view('time.edit', [
            'entry' => $timeEntry,
            'activeRole' => $activeRole,
            'useGuideLayout' => $activeRole === Roles::GUIDE,
        ]);
    }

    public function update(Request $request, TimeEntry $timeEntry): RedirectResponse
    {
        $this->authorize('update', $timeEntry);

        $previousWorkDate = $timeEntry->work_date->format('Y-m-d');

        $validated = $request->validate([
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'user_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['break_minutes'] = (int) ($validated['break_minutes'] ?? 0);

        $newWorkDate = Carbon::parse($validated['start_at'])->toDateString();

        PayrollLockService::assertWorkDateUnlockedForUser($previousWorkDate);
        PayrollLockService::assertWorkDateUnlockedForUser($newWorkDate);

        $this->auditChanges($timeEntry, $validated, $request->user()->id, 'user');

        $timeEntry->fill($validated);

        if ($timeEntry->end_at && $timeEntry->status === TimeEntry::STATUS_OPEN) {
            $timeEntry->status = TimeEntry::STATUS_DRAFT;
        }

        $timeEntry->work_date = $timeEntry->start_at->toDateString();

        $timeEntry->save();

        return redirect()
            ->route('time.index')
            ->with('success', 'Tiden har uppdaterats. Originalstämplingen finns kvar.');
    }

    public function submit(Request $request, TimeEntry $timeEntry): RedirectResponse
    {
        $this->authorize('submit', $timeEntry);
        abort_if($timeEntry->status === TimeEntry::STATUS_OPEN, 422, 'Du måste stämpla ut eller ange sluttid innan du skickar in tiden.');
        PayrollLockService::assertWorkDateUnlockedForUser($timeEntry->work_date->format('Y-m-d'));

        if ($timeEntry->status !== TimeEntry::STATUS_SUBMITTED) {
            TimeEntryAudit::create([
                'time_entry_id' => $timeEntry->id,
                'changed_by' => $request->user()->id,
                'field' => 'status',
                'old_value' => $timeEntry->status,
                'new_value' => TimeEntry::STATUS_SUBMITTED,
                'source' => 'user',
                'reason' => 'Användaren skickade in tiden.',
            ]);

            $timeEntry->update(['status' => TimeEntry::STATUS_SUBMITTED]);
        }

        return redirect()
            ->route('time.index')
            ->with('success', 'Tiden har skickats in.');
    }

    private function auditChanges(TimeEntry $entry, array $newValues, int $changedBy, string $source): void
    {
        $fields = ['start_at', 'end_at', 'break_minutes', 'user_comment'];

        foreach ($fields as $field) {
            if (! array_key_exists($field, $newValues)) {
                continue;
            }

            $oldValue = $entry->{$field};
            $newValue = $newValues[$field];

            $oldNormalized = $oldValue instanceof \DateTimeInterface ? $oldValue->format('Y-m-d H:i:s') : (string) ($oldValue ?? '');
            $newNormalized = $newValue instanceof \DateTimeInterface ? $newValue->format('Y-m-d H:i:s') : (string) ($newValue ?? '');

            if ($oldNormalized === $newNormalized) {
                continue;
            }

            TimeEntryAudit::create([
                'time_entry_id' => $entry->id,
                'changed_by' => $changedBy,
                'field' => $field,
                'old_value' => $oldNormalized,
                'new_value' => $newNormalized,
                'source' => $source,
                'reason' => 'Ändrad av användaren.',
            ]);
        }
    }
}
