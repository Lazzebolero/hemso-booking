<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Models\TimeEntryAudit;
use App\Services\PayrollLockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TimeClockController extends Controller
{
    private function resolveClientOccurredAt(Request $request): ?Carbon
    {
        $raw = $request->input('client_occurred_at');

        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        try {
            $occurredAt = Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }

        // Best-effort guardrails: accept only reasonable timestamps.
        // This is not an anti-cheat mechanism.
        if ($occurredAt->lt(now()->subHours(12))) {
            return null;
        }

        if ($occurredAt->gt(now()->addMinutes(5))) {
            return null;
        }

        return $occurredAt;
    }

    public function clockIn(Request $request): RedirectResponse
    {
        $this->authorize('clock', TimeEntry::class);

        $user = $request->user();
        $openEntry = TimeEntry::currentOpenForUser($user->id);

        if ($openEntry) {
            return redirect()
                ->route('time.index')
                ->with('warning', 'Du har redan ett öppet pass. Stämpla ut det innan du startar ett nytt.');
        }

        $now = $this->resolveClientOccurredAt($request) ?? now();
        $now = $now->timezone(config('app.timezone'));

        PayrollLockService::assertWorkDateUnlockedForUser($now->toDateString());

        TimeEntry::create([
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
            'clock_in_at_original' => $now,
            'start_at' => $now,
            'break_minutes' => 0,
            'status' => TimeEntry::STATUS_OPEN,
        ]);

        return redirect()
            ->route('time.index')
            ->with('success', 'Du är instämplad.');
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $this->authorize('clock', TimeEntry::class);

        $user = $request->user();
        $openEntry = TimeEntry::currentOpenForUser($user->id);

        if (! $openEntry) {
            return redirect()
                ->route('time.index')
                ->with('warning', 'Du har inget öppet pass att stämpla ut från.');
        }

        $now = $this->resolveClientOccurredAt($request) ?? now();
        $now = $now->timezone(config('app.timezone'));

        TimeEntryAudit::create([
            'time_entry_id' => $openEntry->id,
            'changed_by' => $user->id,
            'field' => 'status',
            'old_value' => $openEntry->status,
            'new_value' => TimeEntry::STATUS_DRAFT,
            'source' => 'user',
            'reason' => 'Användaren stämplade ut.',
        ]);

        $openEntry->update([
            'clock_out_at_original' => $now,
            'end_at' => $now,
            'status' => TimeEntry::STATUS_DRAFT,
        ]);

        return redirect()
            ->route('time.index')
            ->with('success', 'Du är utstämplad. Kontrollera tiden och skicka in när den stämmer.');
    }
}
