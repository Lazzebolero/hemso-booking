<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class SystemHealthReportService
{
    /**
     * @return array{
     *     generated_at: Carbon,
     *     overall_status: string,
     *     summary: array{ok: int, warning: int, error: int},
     *     checks: list<array{status: string, title: string, message: string, items: array<string, string>, details?: list<array{command: string, message: string, failed_at_label: string}>}>
     * }
     */
    public function buildReport(bool $includeHttpCheck = true): array
    {
        $checks = [
            $this->checkApp(),
            $this->checkDatabase(),
            $this->checkMigrations(),
            $this->checkStorage(),
            $this->checkFrontend(),
            $this->checkCache(),
            $this->checkQueue(),
            $this->checkMail(),
            $this->checkScheduler(),
            $this->checkLogs(),
        ];

        if ($includeHttpCheck) {
            $checks[] = $this->checkHttpUp();
        }

        $summary = [
            'ok' => 0,
            'warning' => 0,
            'error' => 0,
        ];

        foreach ($checks as $check) {
            $summary[$check['status']] = ($summary[$check['status']] ?? 0) + 1;
        }

        $overallStatus = $summary['error'] > 0
            ? 'error'
            : ($summary['warning'] > 0 ? 'warning' : 'ok');

        return [
            'generated_at' => now(),
            'overall_status' => $overallStatus,
            'summary' => $summary,
            'checks' => $checks,
        ];
    }

    /**
     * @return array{status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkApp(): array
    {
        return [
            'status' => config('app.debug') && app()->environment('production') ? 'warning' : 'ok',
            'title' => 'Applikation',
            'items' => [
                'Miljö' => app()->environment(),
                'Debug' => config('app.debug') ? 'På' : 'Av',
                'Laravel' => app()->version(),
                'PHP' => PHP_VERSION,
            ],
            'message' => config('app.debug') && app()->environment('production')
                ? 'APP_DEBUG är på. Det bör vara av i produktion.'
                : 'Applikationsinställningar ser bra ut.',
        ];
    }

    /**
     * @return array{status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            $tablesOk = Schema::hasTable('users')
                && Schema::hasTable('roles')
                && Schema::hasTable('bookings')
                && Schema::hasTable('tours');

            return [
                'status' => $tablesOk ? 'ok' : 'warning',
                'title' => 'Databas',
                'items' => [
                    'Connection' => config('database.default'),
                ],
                'message' => $tablesOk
                    ? 'Databasen svarar och kritiska tabeller finns.'
                    : 'Databasen svarar, men en eller flera kritiska tabeller saknas.',
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'title' => 'Databas',
                'items' => [
                    'Fel' => $e->getMessage(),
                ],
                'message' => 'Databasen kunde inte nås.',
            ];
        }
    }

    /**
     * @return array{status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkMigrations(): array
    {
        try {
            $migrator = app('migrator');
            $files = $migrator->getMigrationFiles([database_path('migrations')]);
            $ran = $migrator->getRepository()->getRan();
            $pending = count(array_diff(array_keys($files), $ran));
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'title' => 'Migrationer',
                'items' => [],
                'message' => 'Kunde inte läsa migrationsstatus: '.$e->getMessage(),
            ];
        }

        return [
            'status' => $pending > 0 ? 'error' : 'ok',
            'title' => 'Migrationer',
            'items' => [
                'Väntande' => (string) $pending,
            ],
            'message' => $pending > 0
                ? "{$pending} väntande migration(er). Kör dem med knappen på den här sidan."
                : 'Alla migrationer är körda.',
        ];
    }

    /**
     * @return array{status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkStorage(): array
    {
        $storageWritable = is_writable(storage_path());
        $cacheWritable = is_writable(base_path('bootstrap/cache'));
        $publicStorageExists = File::exists(public_path('storage'));

        $status = ($storageWritable && $cacheWritable && $publicStorageExists) ? 'ok' : 'warning';

        return [
            'status' => $status,
            'title' => 'Storage och filer',
            'items' => [
                'storage skrivbar' => $storageWritable ? 'OK' : 'Ej skrivbar',
                'bootstrap/cache skrivbar' => $cacheWritable ? 'OK' : 'Ej skrivbar',
                'public/storage-länk' => $publicStorageExists ? 'Finns' : 'Saknas',
            ],
            'message' => $status === 'ok'
                ? 'Storage och cachemappar ser bra ut.'
                : 'Kontrollera filrättigheter eller kör php artisan storage:link.',
        ];
    }

    /**
     * @return array{status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkFrontend(): array
    {
        $manifestExists = File::exists(public_path('build/manifest.json'));

        if ($manifestExists) {
            return [
                'status' => 'ok',
                'title' => 'Frontend (Vite)',
                'items' => [],
                'message' => 'build/manifest.json finns.',
            ];
        }

        if (app()->environment('local', 'testing')) {
            return [
                'status' => 'ok',
                'title' => 'Frontend (Vite)',
                'items' => [],
                'message' => 'build/manifest.json saknas (OK i local/testing).',
            ];
        }

        return [
            'status' => 'error',
            'title' => 'Frontend (Vite)',
            'items' => [],
            'message' => 'build/manifest.json saknas. Kör npm run build.',
        ];
    }

    /**
     * @return array{status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkCache(): array
    {
        $configCached = app()->configurationIsCached();

        if (app()->environment('production') && ! $configCached) {
            return [
                'status' => 'warning',
                'title' => 'Cache',
                'items' => [
                    'Config cache' => 'Ej cachad',
                    'Routes cache' => app()->routesAreCached() ? 'Aktiv' : 'Ej cachad',
                ],
                'message' => 'Cache fungerar, men config är inte cachad i production.',
            ];
        }

        return [
            'status' => 'ok',
            'title' => 'Cache',
            'items' => [
                'Config cache' => $configCached ? 'Aktiv' : 'Ej cachad',
                'Routes cache' => app()->routesAreCached() ? 'Aktiv' : 'Ej cachad',
            ],
            'message' => 'Cache-status ser bra ut.',
        ];
    }

    /**
     * @return array{status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkQueue(): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [
                'status' => 'ok',
                'title' => 'Jobbkö',
                'items' => [
                    'Driver' => config('queue.default', 'sync'),
                ],
                'message' => 'Jobbkön ser normal ut.',
            ];
        }

        $failedCount = (int) DB::table('failed_jobs')->count();

        return [
            'status' => $failedCount > 0 ? 'warning' : 'ok',
            'title' => 'Jobbkö',
            'items' => [
                'Misslyckade jobb' => (string) $failedCount,
            ],
            'message' => $failedCount > 0
                ? "{$failedCount} misslyckade jobb i kön."
                : 'Jobbkön ser normal ut.',
        ];
    }

    /**
     * @return array{status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkMail(): array
    {
        $mailer = config('mail.default');
        $from = config('mail.from.address');

        return [
            'status' => ($mailer && $from) ? 'ok' : 'warning',
            'title' => 'E-post',
            'items' => [
                'Mailer' => $mailer ?: 'Saknas',
                'Från-adress' => $from ?: 'Saknas',
            ],
            'message' => ($mailer && $from)
                ? 'E-postkonfiguration finns.'
                : 'E-postkonfiguration verkar ofullständig.',
        ];
    }

    /**
     * @return array{status: string, title: string, message: string, items: array<string, string>, details?: list<array{command: string, message: string, failed_at_label: string}>}
     */
    private function checkScheduler(): array
    {
        $failures = app(SchedulerFailureStore::class);
        $recentFailures = $failures->recent(8, now()->subDay());
        $failureCount = count($recentFailures);
        $details = $this->schedulerFailureDetails($recentFailures);

        $path = storage_path('app/scheduler-heartbeat.json');
        $items = [
            'Misslyckade jobb (24 h)' => (string) $failureCount,
        ];

        if (! file_exists($path)) {
            if ($failureCount > 0) {
                return [
                    'status' => 'error',
                    'title' => 'Scheduler / Cron',
                    'items' => $items,
                    'details' => $details,
                    'message' => $failureCount === 1
                        ? 'Ett schemalagt jobb misslyckades senaste 24 timmarna.'
                        : "{$failureCount} schemalagda jobb misslyckades senaste 24 timmarna.",
                ];
            }

            return [
                'status' => 'warning',
                'title' => 'Scheduler / Cron',
                'items' => $items,
                'message' => 'Laravel scheduler verkar inte ha kört ännu. Kontrollera cron-jobbet.',
            ];
        }

        try {
            $data = json_decode((string) file_get_contents($path), true);
            $ranAt = isset($data['ran_at']) ? Carbon::parse($data['ran_at']) : null;

            if (! $ranAt) {
                throw new \RuntimeException('Ogiltig heartbeat.');
            }

            $ageSeconds = $ranAt->diffInSeconds(now());
            $items = [
                'Senaste körning' => $ranAt->format('Y-m-d H:i:s'),
                'Misslyckade jobb (24 h)' => (string) $failureCount,
            ];

            if ($failureCount > 0) {
                return [
                    'status' => 'error',
                    'title' => 'Scheduler / Cron',
                    'items' => $items,
                    'details' => $details,
                    'message' => $failureCount === 1
                        ? 'Ett schemalagt jobb misslyckades senaste 24 timmarna.'
                        : "{$failureCount} schemalagda jobb misslyckades senaste 24 timmarna.",
                ];
            }

            return [
                'status' => $ageSeconds <= 180 ? 'ok' : 'warning',
                'title' => 'Scheduler / Cron',
                'items' => $items,
                'message' => $ageSeconds <= 180
                    ? 'Laravel scheduler körs som den ska.'
                    : 'Schedulern har inte uppdaterat heartbeat på över 3 minuter.',
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'title' => 'Scheduler / Cron',
                'items' => [
                    'Fel' => $e->getMessage(),
                    'Misslyckade jobb (24 h)' => (string) $failureCount,
                ],
                'details' => $details,
                'message' => 'Heartbeat-filen kunde inte läsas.',
            ];
        }
    }

    /**
     * @param  list<array{command: string, exit_code: int, message: string, failed_at: string, failed_at_label: string}>  $failures
     * @return list<array{command: string, message: string, failed_at_label: string}>
     */
    private function schedulerFailureDetails(array $failures): array
    {
        return array_map(
            fn (array $failure): array => [
                'command' => $failure['command'],
                'message' => $failure['message'] !== ''
                    ? $failure['message']
                    : 'Avslutades med kod '.$failure['exit_code'],
                'failed_at_label' => $failure['failed_at_label'],
            ],
            $failures,
        );
    }

    /**
     * @return array{status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkLogs(): array
    {
        $logFile = storage_path('logs/laravel.log');

        if (! File::exists($logFile)) {
            return [
                'status' => 'ok',
                'title' => 'Loggar',
                'items' => [],
                'message' => 'Ingen loggfil hittades ännu.',
            ];
        }

        $since = now()->subDay();
        $errorCount = 0;

        foreach (File::lines($logFile) as $line) {
            if (! preg_match('/^\[([^\]]+)\]\s+\S+\.(ERROR|CRITICAL|ALERT|EMERGENCY):/', (string) $line, $matches)) {
                continue;
            }

            try {
                $loggedAt = Carbon::parse($matches[1]);
            } catch (\Throwable) {
                continue;
            }

            if ($loggedAt->greaterThanOrEqualTo($since)) {
                $errorCount++;
            }
        }

        return [
            'status' => $errorCount > 0 ? 'warning' : 'ok',
            'title' => 'Loggar',
            'items' => [
                'Felrader (24 h)' => (string) $errorCount,
            ],
            'message' => $errorCount > 0
                ? "{$errorCount} felrader i loggen senaste 24 timmarna."
                : 'Inga felrader i loggen senaste 24 timmarna.',
        ];
    }

    /**
     * @return array{status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkHttpUp(): array
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        if ($baseUrl === '') {
            return [
                'status' => 'warning',
                'title' => 'HTTP /up',
                'items' => [],
                'message' => 'APP_URL saknas — kunde inte testa /up.',
            ];
        }

        try {
            $response = Http::timeout(15)
                ->withOptions(['verify' => ! app()->environment('local', 'testing')])
                ->get($baseUrl.'/up');
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'title' => 'HTTP /up',
                'items' => [],
                'message' => 'Kunde inte nå /up: '.$e->getMessage(),
            ];
        }

        return [
            'status' => $response->successful() ? 'ok' : 'error',
            'title' => 'HTTP /up',
            'items' => [
                'Statuskod' => (string) $response->status(),
            ],
            'message' => $response->successful()
                ? 'Applikationens hälsosvar är OK.'
                : '/up svarade '.$response->status().'.',
        ];
    }
}
