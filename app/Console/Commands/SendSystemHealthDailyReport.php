<?php

namespace App\Console\Commands;

use App\Mail\SystemHealthDailyReportMail;
use App\Support\SystemHealthChecker;
use App\Support\SystemHealthRecorder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendSystemHealthDailyReport extends Command
{
    protected $signature = 'system-health:send-daily-report
                            {--to= : Override recipient email address}';

    protected $description = 'Send a daily system health status email';

    public function handle(SystemHealthChecker $checker, SystemHealthRecorder $recorder): int
    {
        $recipient = $this->recipient();

        if ($recipient === '') {
            $this->warn('No SYSTEM_HEALTH_REPORT_EMAIL configured.');

            return self::SUCCESS;
        }

        $checks = $checker->dashboardChecks();
        $overallStatus = $checker->overallStatus($checks);

        $recorder->record($checks, $overallStatus);

        Mail::to($recipient)->send(new SystemHealthDailyReportMail(
            $overallStatus,
            $checks,
            now()->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            $this->dashboardUrl(),
        ));

        $this->info("Daily system health report sent to {$recipient}.");

        return self::SUCCESS;
    }

    private function recipient(): string
    {
        $option = $this->option('to');

        if (is_string($option) && trim($option) !== '') {
            return trim($option);
        }

        return trim((string) config('services.system_health.report_email'));
    }

    private function dashboardUrl(): string
    {
        return rtrim((string) config('app.url'), '/').route('admin.system-health.index', absolute: false);
    }
}
