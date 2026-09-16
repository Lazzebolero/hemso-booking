<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Models\TimeEntryAudit;
use App\Services\PayrollLockService;
use App\Services\TimeClockLocationPayload;
use App\Services\TimeClockStationRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

    private function resolveClockStationKey(Request $request): ?string
    {
        $stationKey = $request->input('clock_station');

        if (! is_string($stationKey) || $stationKey === '') {
            return null;
        }

        $station = TimeClockStationRegistry::findByKey($stationKey);

        if (! $station) {
            throw new HttpException(422, 'Ogiltig stämpelstation.');
        }

        $activeRole = session('active_role');

        if (! is_string($activeRole) || ! TimeClockStationRegistry::userMayAccess($station, $activeRole)) {
            throw new HttpException(403, 'Din roll kan inte stämpla vid denna station.');
        }

        return $stationKey;
    }

    private function redirectAfterClock(Request $request, string $message, string $flashKey = 'success'): RedirectResponse
    {
        $token = $request->input('scan_token');

        if (is_string($token) && $token !== '' && TimeClockStationRegistry::findByToken($token)) {
            return redirect()
                ->route('time.scan', ['token' => $token])
                ->with($flashKey, $message);
        }

        return redirect()
            ->route('time.index')
            ->with($flashKey, $message);
    }

    private function rejectDirectStampIfRequired(Request $request): ?RedirectResponse
    {
        $activeRole = session('active_role');

        if (! is_string($activeRole) || ! TimeClockStationRegistry::requiresQrStamp($activeRole)) {
            return null;
        }

        if ($request->filled('clock_station')) {
            return null;
        }

        return redirect()
            ->route('time.index')
            ->with('warning', 'Stämpling sker via QR vid din station. Använd QR-ikonen i menyn eller skanna skylten.');
    }

    public function clockIn(Request $request): RedirectResponse
    {
        $this->authorize('clock', TimeEntry::class);

        if ($redirect = $this->rejectDirectStampIfRequired($request)) {
            return $redirect;
        }

        $user = $request->user();
        $openEntry = TimeEntry::currentOpenForUser($user->id);

        if ($openEntry) {
            return $this->redirectAfterClock(
                $request,
                'Du har redan ett öppet pass. Stämpla ut det innan du startar ett nytt.',
                'warning'
            );
        }

        $now = $this->resolveClientOccurredAt($request) ?? now();
        $now = $now->timezone(config('app.timezone'));

        PayrollLockService::assertWorkDateUnlockedForUser($now->toDateString());

        $stationKey = $this->resolveClockStationKey($request);
        $location = TimeClockLocationPayload::fromRequest($request);

        $attributes = [
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
            'clock_in_at_original' => $now,
            'start_at' => $now,
            'break_minutes' => 0,
            'status' => TimeEntry::STATUS_OPEN,
        ];

        if ($stationKey !== null) {
            $attributes['clock_in_station'] = $stationKey;
            $attributes = array_merge($attributes, $location->clockInAttributes());
        }

        TimeEntry::create($attributes);

        $message = 'Du är instämplad.';

        if ($stationKey !== null && ! $location->isOk()) {
            $message .= ' Plats kunde inte hämtas — stämplingen är sparad.';
        }

        return $this->redirectAfterClock($request, $message);
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $this->authorize('clock', TimeEntry::class);

        if ($redirect = $this->rejectDirectStampIfRequired($request)) {
            return $redirect;
        }

        $user = $request->user();
        $openEntry = TimeEntry::currentOpenForUser($user->id);

        if (! $openEntry) {
            return $this->redirectAfterClock(
                $request,
                'Du har inget öppet pass att stämpla ut från.',
                'warning'
            );
        }

        $now = $this->resolveClientOccurredAt($request) ?? now();
        $now = $now->timezone(config('app.timezone'));

        $stationKey = $this->resolveClockStationKey($request);
        $location = TimeClockLocationPayload::fromRequest($request);

        TimeEntryAudit::create([
            'time_entry_id' => $openEntry->id,
            'changed_by' => $user->id,
            'field' => 'status',
            'old_value' => $openEntry->status,
            'new_value' => TimeEntry::STATUS_DRAFT,
            'source' => 'user',
            'reason' => 'Användaren stämplade ut.',
        ]);

        $update = [
            'clock_out_at_original' => $now,
            'end_at' => $now,
            'status' => TimeEntry::STATUS_DRAFT,
        ];

        if ($stationKey !== null) {
            $update['clock_out_station'] = $stationKey;
            $update = array_merge($update, $location->clockOutAttributes());
        }

        $openEntry->update($update);

        $message = 'Du är utstämplad. Kontrollera tiden och skicka in när den stämmer.';

        if ($stationKey !== null && ! $location->isOk()) {
            $message .= ' Plats kunde inte hämtas — stämplingen är sparad.';
        }

        return $this->redirectAfterClock($request, $message);
    }
}
