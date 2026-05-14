<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLockedPayrollPeriodRequest;
use App\Models\LockedPayrollPeriod;
use App\Services\LogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminLockedPayrollPeriodController extends Controller
{
    public function index(): View
    {
        $locks = LockedPayrollPeriod::query()
            ->with('lockedByUser')
            ->orderByDesc('start_date')
            ->get();

        return view('admin.time.payroll-locks.index', [
            'locks' => $locks,
        ]);
    }

    public function store(StoreLockedPayrollPeriodRequest $request): RedirectResponse
    {
        $lock = LockedPayrollPeriod::query()->create([
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'locked_by' => $request->user()->id,
            'locked_at' => now(),
        ]);

        LogService::log(
            'locked_payroll_period',
            $lock->id,
            'locked',
            null,
            [
                'start_date' => $lock->start_date->format('Y-m-d'),
                'end_date' => $lock->end_date->format('Y-m-d'),
            ],
            'Lade lås på löneperiod (datumintervall).'
        );

        return redirect()
            ->route('admin.time.payroll-locks.index')
            ->with('success', 'Perioden är låst för personaländringar.');
    }

    public function destroy(LockedPayrollPeriod $lockedPayrollPeriod): RedirectResponse
    {
        $id = $lockedPayrollPeriod->id;

        $payload = [
            'start_date' => $lockedPayrollPeriod->start_date->format('Y-m-d'),
            'end_date' => $lockedPayrollPeriod->end_date->format('Y-m-d'),
        ];

        $lockedPayrollPeriod->delete();

        LogService::log(
            'locked_payroll_period',
            $id,
            'unlocked',
            $payload,
            null,
            'Tog bort lås på löneperiod.'
        );

        return redirect()
            ->route('admin.time.payroll-locks.index')
            ->with('success', 'Låset är borttaget.');
    }
}
