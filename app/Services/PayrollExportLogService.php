<?php

namespace App\Services;

class PayrollExportLogService
{
    /**
     * @param  array<string, mixed>  $period  PayrollPeriodService period
     */
    public static function logDownload(string $action, array $period, ?int $targetUserId = null, ?string $targetUserName = null): void
    {
        $newValues = [
            'period_start' => $period['start_date'],
            'period_end' => $period['end_date'],
            'period_label' => $period['label'],
            'target_user_id' => $targetUserId,
            'target_user_name' => $targetUserName,
        ];

        $description = match ($action) {
            'payroll_pdf_all' => 'Laddade ner löneunderlag PDF (alla).',
            'payroll_pdf_person' => 'Laddade ner löneunderlag PDF (en person).',
            'payroll_csv_entries' => 'Exporterade tidrapport CSV (pass).',
            'payroll_csv_summary' => 'Exporterade tidrapport CSV (summering).',
            'payroll_excel' => 'Exporterade tidrapport Excel.',
            default => 'Exporterade löneunderlag.',
        };

        LogService::log('payroll_export', null, $action, null, $newValues, $description);
    }
}
