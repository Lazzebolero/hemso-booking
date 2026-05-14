<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TimeEntriesPeriodExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\PayrollPeriodFilterRequest;
use App\Services\PayrollExportLogService;
use App\Services\PayrollPeriodService;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminTimeExportController extends Controller
{
    public function export(PayrollPeriodFilterRequest $request): BinaryFileResponse
    {
        $period = PayrollPeriodService::resolveFromRequest($request->payrollPeriodQuery());

        $filename = 'tidrapport_'.$period['file_label'].'.xlsx';

        $userId = $request->filled('user_id') ? (int) $request->validated('user_id') : null;
        $status = $request->filled('status') ? (string) $request->validated('status') : null;

        PayrollExportLogService::logDownload('payroll_excel', $period, $userId);

        return Excel::download(
            new TimeEntriesPeriodExport($period['start'], $period['end'], $userId, $status),
            $filename
        );
    }
}
