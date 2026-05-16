<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SystemHealthTestMail;
use App\Support\SystemHealthChecker;
use App\Support\SystemHealthRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function index(Request $request, SystemHealthChecker $checker, SystemHealthRecorder $recorder): View
    {
        $payload = $this->buildPayload($checker);

        $recorder->record($payload['checks'], $payload['overall_status']);

        return view('admin.system-health.index', [
            'checks' => $payload['checks'],
            'overallStatus' => $payload['overall_status'],
            'checkedAt' => now(),
            'history' => $recorder->recent(15),
            'monitorConfigured' => (string) config('services.system_health.monitor_token') !== '',
            'monitorUrl' => $request->getSchemeAndHttpHost().route('health.monitor', absolute: false),
        ]);
    }

    public function sendTestMail(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user?->email) {
            return back()->with('warning', 'Ditt konto saknar e-postadress.');
        }

        try {
            Mail::to($user->email)->send(new SystemHealthTestMail(
                $user,
                now()->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            ));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('warning', 'Testmail kunde inte skickas: '.$exception->getMessage());
        }

        return back()->with('success', 'Testmail skickades till '.$user->email.'.');
    }

    public function statusJson(SystemHealthChecker $checker): JsonResponse
    {
        return response()->json($this->buildPayload($checker));
    }

    public function monitor(SystemHealthChecker $checker): JsonResponse
    {
        return response()->json($this->buildPayload($checker));
    }

    /**
     * @return array{overall_status: string, checked_at: string, checks: array<string, array{key: string, status: string, title: string, message: string, items: array<string, string>, groups: list<array{title: string, items: array<string, string>}>}>, summary: array{ok: int, warning: int, error: int}}
     */
    private function buildPayload(SystemHealthChecker $checker): array
    {
        $checks = $checker->dashboardChecks();
        $overallStatus = $checker->overallStatus($checks);

        $summary = ['ok' => 0, 'warning' => 0, 'error' => 0];

        foreach ($checks as $check) {
            if (isset($summary[$check['status']])) {
                $summary[$check['status']]++;
            }
        }

        return [
            'overall_status' => $overallStatus,
            'checked_at' => now()->toIso8601String(),
            'checks' => array_values(array_map(
                fn (array $check): array => [
                    'key' => $check['key'],
                    'status' => $check['status'],
                    'title' => $check['title'],
                    'message' => $check['message'],
                    'items' => $check['items'],
                    'groups' => $check['groups'],
                ],
                $checks
            )),
            'summary' => $summary,
        ];
    }
}
