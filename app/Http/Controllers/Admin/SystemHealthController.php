<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LogService;
use App\Services\SystemHealthReportService;
use App\Support\ActiveRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function __construct(
        private SystemHealthReportService $reportService,
    ) {}

    public function index(): View
    {
        $report = $this->reportService->buildReport(includeHttpCheck: false);

        return view('admin.system-health.index', [
            'checks' => $report['checks'],
            'overallStatus' => $report['overall_status'],
            'canRunMigrations' => ActiveRole::isAdmin(),
        ]);
    }

    public function runMigrations(): RedirectResponse
    {
        abort_unless(ActiveRole::isAdmin(), 403);

        try {
            $exitCode = Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());
        } catch (\Throwable $e) {
            return back()->withErrors([
                'migrate' => 'Kunde inte köra migrationer: '.$e->getMessage(),
            ]);
        }

        LogService::log(
            'system',
            null,
            'migrations_ran',
            null,
            ['exit_code' => $exitCode],
            'Körde väntande databasmigrationer från Systemhälsa'
        );

        if ($exitCode !== 0) {
            return back()->withErrors([
                'migrate' => $output !== '' ? $output : 'Migrationen misslyckades.',
            ]);
        }

        $message = $output !== ''
            ? $output
            : 'Inga väntande migrationer.';

        return back()->with('success', $message);
    }
}
