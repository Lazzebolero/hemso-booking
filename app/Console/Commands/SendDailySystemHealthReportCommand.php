<?php

namespace App\Console\Commands;

use App\Mail\DailySystemHealthReportMail;
use App\Services\SystemHealthReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

class SendDailySystemHealthReportCommand extends Command
{
    protected $signature = 'system-health:send-daily-report';

    protected $description = 'Skickar daglig systemstatus via e-post';

    public function handle(SystemHealthReportService $reportService): int
    {
        $email = config('services.system_health.report_email');

        if (! $email) {
            $this->warn('SYSTEM_HEALTH_REPORT_EMAIL saknas i .env — inget mail skickas.');

            return self::FAILURE;
        }

        $report = $reportService->buildReport(includeHttpCheck: true);
        $systemHealthUrl = Route::has('admin.system-health.index')
            ? route('admin.system-health.index')
            : url('/');

        Mail::to($email)->send(new DailySystemHealthReportMail($report, $systemHealthUrl));

        $this->info("Daglig systemstatus skickad till {$email}.");

        return self::SUCCESS;
    }
}
