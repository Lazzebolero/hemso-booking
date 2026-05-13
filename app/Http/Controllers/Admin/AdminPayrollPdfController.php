<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\PayrollPeriodService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPayrollPdfController extends Controller
{
    public function person(Request $request, User $user): Response
    {
        $period = PayrollPeriodService::resolveFromRequest($request->all());

        $entries = TimeEntry::query()
            ->where('user_id', $user->id)
            ->where('status', TimeEntry::STATUS_APPROVED)
            ->whereBetween('work_date', [$period['start_date'], $period['end_date']])
            ->orderBy('work_date')
            ->orderBy('start_at')
            ->get();

        $totalMinutes = $entries->sum(fn (TimeEntry $entry) => $entry->worked_minutes);
        $totalFormatted = sprintf('%dh %02dm', intdiv($totalMinutes, 60), $totalMinutes % 60);

        $filename = 'loneunderlag_' .
            str($user->name)->ascii()->slug('_') .
            '_' . $period['file_label'] .
            '.pdf';

        $pdf = Pdf::loadView('admin.time.pdf.payroll-person', [
            'user' => $user,
            'entries' => $entries,
            'period' => $period,
            'totalMinutes' => $totalMinutes,
            'totalFormatted' => $totalFormatted,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    public function all(Request $request): Response
    {
        $period = PayrollPeriodService::resolveFromRequest($request->all());

        $users = User::query()
            ->whereHas('timeEntries', function ($query) use ($period) {
                $query
                    ->where('status', TimeEntry::STATUS_APPROVED)
                    ->whereBetween('work_date', [$period['start_date'], $period['end_date']]);
            })
            ->orderBy('name')
            ->get();

        $usersWithEntries = $users->map(function (User $user) use ($period) {
            $entries = TimeEntry::query()
                ->where('user_id', $user->id)
                ->where('status', TimeEntry::STATUS_APPROVED)
                ->whereBetween('work_date', [$period['start_date'], $period['end_date']])
                ->orderBy('work_date')
                ->orderBy('start_at')
                ->get();

            $totalMinutes = $entries->sum(fn (TimeEntry $entry) => $entry->worked_minutes);

            return [
                'user' => $user,
                'entries' => $entries,
                'totalMinutes' => $totalMinutes,
                'totalFormatted' => sprintf('%dh %02dm', intdiv($totalMinutes, 60), $totalMinutes % 60),
            ];
        });

        $filename = 'loneunderlag_alla_' . $period['file_label'] . '.pdf';

        $pdf = Pdf::loadView('admin.time.pdf.payroll-all', [
            'period' => $period,
            'usersWithEntries' => $usersWithEntries,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }
}
