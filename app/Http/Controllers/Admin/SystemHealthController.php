<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function index(): View
    {
        $checks = [
            'app' => $this->checkApp(),
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorage(),
            'cache' => $this->checkCache(),
            'mail' => $this->checkMail(),
            'scheduler' => $this->checkScheduler(),
            'logs' => $this->checkLogs(),
        ];

        $hasWarnings = collect($checks)->contains(fn ($check) => $check['status'] === 'warning');
        $hasErrors = collect($checks)->contains(fn ($check) => $check['status'] === 'error');

        $overallStatus = $hasErrors
            ? 'error'
            : ($hasWarnings ? 'warning' : 'ok');

        return view('admin.system-health.index', [
            'checks' => $checks,
            'overallStatus' => $overallStatus,
        ]);
    }

    private function checkApp(): array
    {
        return [
            'status' => config('app.debug') ? 'warning' : 'ok',
            'title' => 'Applikation',
            'items' => [
                'Miljö' => app()->environment(),
                'Debug' => config('app.debug') ? 'På' : 'Av',
                'Laravel' => app()->version(),
                'PHP' => PHP_VERSION,
                'Timezone' => config('app.timezone'),
            ],
            'message' => config('app.debug')
                ? 'APP_DEBUG är på. Det bör vara av i produktion.'
                : 'Applikationsinställningar ser bra ut.',
        ];
    }

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
                    'Databas' => config('database.connections.' . config('database.default') . '.database'),
                    'users' => Schema::hasTable('users') ? 'OK' : 'Saknas',
                    'roles' => Schema::hasTable('roles') ? 'OK' : 'Saknas',
                    'bookings' => Schema::hasTable('bookings') ? 'OK' : 'Saknas',
                    'tours' => Schema::hasTable('tours') ? 'OK' : 'Saknas',
                ],
                'message' => $tablesOk
                    ? 'Databasen svarar och viktiga tabeller finns.'
                    : 'Databasen svarar, men en eller flera viktiga tabeller saknas.',
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

    private function checkStorage(): array
    {
        $storageWritable = is_writable(storage_path());
        $cacheWritable = is_writable(base_path('bootstrap/cache'));
        $publicStorageExists = File::exists(public_path('storage'));

        $status = ($storageWritable && $cacheWritable && $publicStorageExists)
            ? 'ok'
            : 'warning';

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

    private function checkCache(): array
    {
        return [
            'status' => 'ok',
            'title' => 'Cache',
            'items' => [
                'Config cache' => app()->configurationIsCached() ? 'Aktiv' : 'Ej cachad',
                'Routes cache' => app()->routesAreCached() ? 'Aktiv' : 'Ej cachad',
            ],
            'message' => 'Cache-status hämtad.',
        ];
    }

    private function checkMail(): array
    {
        $mailer = config('mail.default');
        $host = config('mail.mailers.smtp.host');
        $from = config('mail.from.address');

        $status = ($mailer && $from)
            ? 'ok'
            : 'warning';

        return [
            'status' => $status,
            'title' => 'E-post',
            'items' => [
                'Mailer' => $mailer ?: 'Saknas',
                'SMTP host' => $host ?: 'Ej satt / ej SMTP',
                'Från-adress' => $from ?: 'Saknas',
            ],
            'message' => $status === 'ok'
                ? 'E-postkonfiguration finns.'
                : 'E-postkonfiguration verkar ofullständig.',
        ];
    }

    private function checkScheduler(): array
    {
        $path = storage_path('app/scheduler-heartbeat.json');

        if (! file_exists($path)) {
            return [
                'status' => 'warning',
                'title' => 'Scheduler / Cron',
                'items' => [
                    'Status' => 'Ingen heartbeat hittad',
                    'Sökväg' => $path,
                    'Senaste körning' => '-',
                    'Ålder' => '-',
                ],
                'message' => 'Laravel scheduler verkar inte ha kört ännu. Kontrollera cron-jobbet på webbhotellet.',
            ];
        }

        try {
            $data = json_decode(file_get_contents($path), true);

            $ranAt = isset($data['ran_at'])
                ? Carbon::parse($data['ran_at'])
                : null;

            if (! $ranAt) {
                throw new \RuntimeException('Ogiltig heartbeat.');
            }

            $ageSeconds = $ranAt->diffInSeconds(now());
            $ageMinutes = round($ageSeconds / 60, 1);

            $status = $ageSeconds <= 180 ? 'ok' : 'warning';

            return [
                'status' => $status,
                'title' => 'Scheduler / Cron',
                'items' => [
                    'Status' => $status === 'ok' ? 'OK' : 'För gammal heartbeat',
                    'Sökväg' => $path,
                    'Senaste körning' => $ranAt->format('Y-m-d H:i:s'),
                    'Ålder' => $ageMinutes . ' minuter',
                ],
                'message' => $status === 'ok'
                    ? 'Laravel scheduler körs som den ska.'
                    : 'Schedulern har inte uppdaterat heartbeat på över 3 minuter.',
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'title' => 'Scheduler / Cron',
                'items' => [
                    'Sökväg' => $path,
                    'Fel' => $e->getMessage(),
                ],
                'message' => 'Heartbeat-filen kunde inte läsas.',
            ];
        }
    }

    private function checkLogs(): array
    {
        $logFile = storage_path('logs/laravel.log');
        $exists = File::exists($logFile);
        $size = $exists ? File::size($logFile) : 0;
        $modified = $exists ? date('Y-m-d H:i', File::lastModified($logFile)) : '-';

        return [
            'status' => $exists ? 'ok' : 'warning',
            'title' => 'Loggar',
            'items' => [
                'laravel.log' => $exists ? 'Finns' : 'Saknas',
                'Storlek' => $exists ? $this->formatBytes($size) : '-',
                'Senast ändrad' => $modified,
            ],
            'message' => $exists
                ? 'Loggfil finns.'
                : 'Ingen Laravel-loggfil hittades ännu.',
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}