<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TimeEntriesPeriodExport;
use App\Http\Controllers\Controller;
use App\Services\PayrollPeriodService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminTimeExportController extends Controller
{
    public function export(Request $request): BinaryFileResponse
    {
        $period = PayrollPeriodService::resolveFromRequest($request->all());

        $filename = 'tidrapport_' . $period['file_label'] . '.xlsx';

        return Excel::download(
            new TimeEntriesPeriodExport($period['start'], $period['end']),
            $filename
        );
    }
}
