<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayrollPeriodFilterRequest;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\PayrollExportLogService;
use App\Services\PayrollPeriodService;
use App\Services\PayrollReadinessService;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class AdminPayrollPdfController extends Controller
{
    public function person(PayrollPeriodFilterRequest $request, User $user): Response
    {
        $period = PayrollPeriodService::resolveFromRequest($request->payrollPeriodQuery());
        $readiness = PayrollReadinessService::assessPeriod($period, $user->id);

        if (! $readiness['pdf_ready'] && ! $request->boolean('ack')) {
            return response()->view('admin.time.payroll-pdf-confirm', [
                'mode' => 'person',
                'user' => $user,
                'period' => $period,
                'readiness' => $readiness,
                'downloadUrl' => route('admin.time.payroll-pdf.person', array_merge(
                    $request->query(),
                    ['user' => $user->id, 'ack' => 1]
                )),
                'backUrl' => route('admin.time.index', $request->query()),
            ]);
        }

        $filename = 'loneunderlag_'.
            str($user->name)->ascii()->slug('_').
            '_'.$period['file_label'].
            '.pdf';

        PayrollExportLogService::logDownload('payroll_pdf_person', $period, $user->id, $user->name);

        return $this->makePersonPdfResponse($user, $period, $filename);
    }

    public function all(PayrollPeriodFilterRequest $request): Response
    {
        $period = PayrollPeriodService::resolveFromRequest($request->payrollPeriodQuery());
        $readiness = PayrollReadinessService::assessPeriod($period);

        if (! $readiness['pdf_ready'] && ! $request->boolean('ack')) {
            return response()->view('admin.time.payroll-pdf-confirm', [
                'mode' => 'all',
                'user' => null,
                'period' => $period,
                'readiness' => $readiness,
                'downloadUrl' => route('admin.time.payroll-pdf.all', array_merge($request->query(), ['ack' => 1])),
                'backUrl' => route('admin.time.index', $request->query()),
            ]);
        }

        $filename = 'loneunderlag_alla_'.$period['file_label'].'.pdf';

        PayrollExportLogService::logDownload('payroll_pdf_all', $period);

        return $this->makeAllPdfResponse($period, $filename);
    }

    /**
     * @param  array<string, mixed>  $period
     */
    private function makePersonPdfResponse(User $user, array $period, string $filename): Response
    {
        $entries = TimeEntry::query()
            ->where('user_id', $user->id)
            ->where('status', TimeEntry::STATUS_APPROVED)
            ->whereBetween('work_date', [$period['start_date'], $period['end_date']])
            ->orderBy('work_date')
            ->orderBy('start_at')
            ->get();

        $totalMinutes = $entries->sum(fn (TimeEntry $entry) => $entry->worked_minutes);
        $totalFormatted = sprintf('%dh %02dm', intdiv($totalMinutes, 60), $totalMinutes % 60);

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

    /**
     * @param  array<string, mixed>  $period
     */
    private function makeAllPdfResponse(array $period, string $filename): Response
    {
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

        $pdf = Pdf::loadView('admin.time.pdf.payroll-all', [
            'period' => $period,
            'usersWithEntries' => $usersWithEntries,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }
}
