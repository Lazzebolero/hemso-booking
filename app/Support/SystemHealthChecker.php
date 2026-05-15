<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class SystemHealthChecker
{
    /**
     * @return array<string, array{key: string, status: string, title: string, message: string, items: array<string, string>}>
     */
    public function dashboardChecks(): array
    {
        return [
            'app' => $this->checkApplication(),
            'database' => $this->checkDatabase(),
            'migrations' => $this->checkMigrations(),
            'storage' => $this->checkStorage(),
            'frontend' => $this->checkFrontendBuild(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'mail' => $this->checkMail(),
            'scheduler' => $this->checkScheduler(),
            'logs' => $this->checkLogs(),
            'http' => $this->checkHttpHealth(),
        ];
    }

    /**
     * @return list<array{name: string, status: string, message: string}>
     */
    public function deploySmokeChecks(?string $baseUrl = null): array
    {
        $checks = [
            $this->flatFromRich($this->checkAppKey(), 'APP_KEY'),
            $this->flatFromRich($this->checkEnvironment(), 'Miljö'),
            $this->flatFromRich($this->checkDatabase(), 'Databas'),
            $this->flatFromRich($this->checkMigrations(), 'Migrationer'),
            $this->flatFromRich($this->checkStorage(), 'Storage'),
            $this->flatFromRich($this->checkFrontendBuild(), 'Frontend (Vite)'),
        ];

        if ($baseUrl !== null && $baseUrl !== '') {
            $checks = array_merge($checks, $this->httpDeployChecks(rtrim($baseUrl, '/')));
        }

        return $checks;
    }

    /**
     * @param  array<string, array{status: string}>  $checks
     */
    public function overallStatus(array $checks): string
    {
        if (collect($checks)->contains(fn (array $check): bool => $check['status'] === 'error')) {
            return 'error';
        }

        if (collect($checks)->contains(fn (array $check): bool => $check['status'] === 'warning')) {
            return 'warning';
        }

        return 'ok';
    }

    /**
     * @param  array{key: string, status: string, title: string, message: string, items: array<string, string>}  $check
     * @return array{name: string, status: string, message: string}
     */
    private function flatFromRich(array $check, string $name): array
    {
        return [
            'name' => $name,
            'status' => $check['status'],
            'message' => $check['message'],
        ];
    }

    /**
     * @return array{key: string, status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkApplication(): array
    {
        $appKey = $this->checkAppKey();
        $environment = $this->checkEnvironment();

        $status = $this->worstStatus($appKey['status'], $environment['status']);

        return $this->rich(
            'app',
            $status,
            'Applikation',
            $status === 'ok'
                ? 'Applikationsinställningar ser bra ut.'
                : ($appKey['message'] !== '' ? $appKey['message'] : $environment['message']),
            groups: [
                ['title' => 'Säkerhet', 'items' => $appKey['items']],
                ['title' => 'Miljö & version', 'items' => $environment['items']],
            ],
        );
    }

    /**
     * @return array{key: string, status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkAppKey(): array
    {
        $key = (string) config('app.key');

        if ($key === '') {
            return $this->rich(
                'app_key',
                'error',
                'APP_KEY',
                'APP_KEY saknas. Kör php artisan key:generate.',
                ['APP_KEY' => 'Saknas']
            );
        }

        return $this->rich(
            'app_key',
            'ok',
            'APP_KEY',
            'Applikationsnyckel är satt.',
            ['APP_KEY' => 'Satt']
        );
    }

    /**
     * @return array{key: string, status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkEnvironment(): array
    {
        $environment = app()->environment();
        $debug = (bool) config('app.debug');

        $items = [
            'Miljö' => $environment,
            'Debug' => $debug ? 'På' : 'Av',
            'Laravel' => app()->version(),
            'PHP' => PHP_VERSION,
            'Timezone' => (string) config('app.timezone'),
        ];

        if ($environment === 'testing') {
            return $this->rich('environment', 'ok', 'Miljö', "Miljö: {$environment} (automatiskt test).", $items);
        }

        if ($environment === 'local') {
            return $this->rich(
                'environment',
                'ok',
                'Miljö',
                $debug
                    ? 'Miljö: local, APP_DEBUG på (förväntat vid lokal utveckling).'
                    : 'Miljö: local, APP_DEBUG av.',
                $items
            );
        }

        if ($environment === 'production' && $debug) {
            return $this->rich('environment', 'error', 'Miljö', 'APP_DEBUG är på i production.', $items);
        }

        if ($debug) {
            return $this->rich('environment', 'warning', 'Miljö', "Miljö: {$environment}, APP_DEBUG är på.", $items);
        }

        return $this->rich('environment', 'ok', 'Miljö', "Miljö: {$environment}, APP_DEBUG av.", $items);
    }

    /**
     * @return array{key: string, status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $exception) {
            return $this->rich(
                'database',
                'error',
                'Databas',
                'Databasen kunde inte nås.',
                ['Fel' => $exception->getMessage()]
            );
        }

        $requiredTables = [
            'users',
            'roles',
            'bookings',
            'tours',
            'visitor_dogs',
            'sessions',
            'login_events',
        ];

        $connectionItems = [
            'Driver' => (string) config('database.default'),
            'Databas' => (string) config('database.connections.'.config('database.default').'.database'),
        ];

        $tableItems = [];
        $missing = [];

        foreach ($requiredTables as $table) {
            $exists = Schema::hasTable($table);
            $tableItems[$table] = $exists ? 'OK' : 'Saknas';

            if (! $exists) {
                $missing[] = $table;
            }
        }

        $groups = [
            ['title' => 'Anslutning', 'items' => $connectionItems],
            ['title' => 'Kritiska tabeller', 'items' => $tableItems],
        ];

        if ($missing !== []) {
            return $this->rich(
                'database',
                'error',
                'Databas',
                'Saknade tabeller: '.implode(', ', $missing).'. Kör php artisan migrate --force.',
                groups: $groups,
            );
        }

        return $this->rich(
            'database',
            'ok',
            'Databas',
            'Databasen svarar och kritiska tabeller finns.',
            groups: $groups,
        );
    }

    /**
     * @return array{key: string, status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkMigrations(): array
    {
        try {
            $migrator = app('migrator');
            $files = $migrator->getMigrationFiles([database_path('migrations')]);
            $ran = $migrator->getRepository()->getRan();
            $pending = array_values(array_diff(array_keys($files), $ran));
            $pendingCount = count($pending);
        } catch (\Throwable $exception) {
            return $this->rich(
                'migrations',
                'error',
                'Migrationer',
                'Kunde inte läsa migrationsstatus.',
                ['Fel' => $exception->getMessage()]
            );
        }

        $items = [
            'Körda' => (string) count($ran),
            'Väntande' => (string) $pendingCount,
        ];

        if ($pendingCount > 0) {
            $pendingLabels = array_map(
                fn (string $migration): string => $this->formatMigrationLabel($migration),
                $pending
            );

            return $this->rich(
                'migrations',
                'error',
                'Migrationer',
                "{$pendingCount} migration(er) finns i koden men är inte registrerade i tabellen migrations. "
                .'Det betyder inte att alla tabeller saknas — kör migrate på servern efter git pull.',
                groups: [
                    ['title' => 'Översikt', 'items' => $items],
                    [
                        'title' => 'Saknade migrationer (kör dessa)',
                        'items' => array_combine(
                            array_map(strval(...), range(1, count($pendingLabels))),
                            $pendingLabels
                        ),
                    ],
                ],
            );
        }

        return $this->rich('migrations', 'ok', 'Migrationer', 'Alla migrationer är körda.', $items);
    }

    /**
     * @return array{key: string, status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkStorage(): array
    {
        $storageWritable = is_writable(storage_path());
        $cacheWritable = is_writable(base_path('bootstrap/cache'));
        $publicStorageExists = File::exists(public_path('storage'));
        $disk = $this->diskSpaceSummary(storage_path());

        $items = [
            'storage skrivbar' => $storageWritable ? 'OK' : 'Ej skrivbar',
            'bootstrap/cache skrivbar' => $cacheWritable ? 'OK' : 'Ej skrivbar',
            'public/storage-länk' => $publicStorageExists ? 'Finns' : 'Saknas',
            'Ledigt utrymme' => $disk['label'],
        ];

        $status = 'ok';

        if (! $storageWritable || ! $cacheWritable || ! $publicStorageExists) {
            $status = 'error';
        } elseif ($disk['status'] === 'warning') {
            $status = 'warning';
        }

        $message = match ($status) {
            'ok' => 'Storage och cachemappar ser bra ut.',
            'warning' => 'Storage fungerar, men diskutrymmet börjar ta slut.',
            default => 'Kontrollera filrättigheter eller kör php artisan storage:link.',
        };

        return $this->rich('storage', $status, 'Storage och filer', $message, $items);
    }

    /**
     * @return array{key: string, status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkFrontendBuild(): array
    {
        $manifestPath = public_path('build/manifest.json');
        $exists = File::exists($manifestPath);

        if ($exists) {
            return $this->rich(
                'frontend',
                'ok',
                'Frontend (Vite)',
                'build/manifest.json finns.',
                [
                    'Manifest' => 'public/build/manifest.json',
                    'Senast ändrad' => date('Y-m-d H:i', File::lastModified($manifestPath)),
                ]
            );
        }

        if (app()->environment('testing')) {
            return $this->rich(
                'frontend',
                'ok',
                'Frontend (Vite)',
                'build/manifest.json saknas (ignoreras i testing).',
                ['Manifest' => 'Saknas']
            );
        }

        if (app()->environment('local')) {
            return $this->rich(
                'frontend',
                'warning',
                'Frontend (Vite)',
                'build/manifest.json saknas (OK om du kör npm run dev).',
                ['Manifest' => 'Saknas']
            );
        }

        return $this->rich(
            'frontend',
            'error',
            'Frontend (Vite)',
            'build/manifest.json saknas. Kör npm ci && npm run build före deploy.',
            ['Manifest' => 'Saknas']
        );
    }

    /**
     * @return array{key: string, status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkCache(): array
    {
        $configCached = app()->configurationIsCached();
        $routesCached = app()->routesAreCached();
        $driver = (string) config('cache.default');

        $readWriteOk = false;
        $readWriteError = null;

        try {
            Cache::put('system_health_probe', 'ok', 10);
            $readWriteOk = Cache::get('system_health_probe') === 'ok';
            Cache::forget('system_health_probe');
        } catch (\Throwable $exception) {
            $readWriteError = $exception->getMessage();
        }

        $items = [
            'Driver' => $driver,
            'Config cache' => $configCached ? 'Aktiv' : 'Ej cachad',
            'Routes cache' => $routesCached ? 'Aktiv' : 'Ej cachad',
            'Läs/skriv-test' => $readWriteOk ? 'OK' : ($readWriteError ?? 'Misslyckades'),
        ];

        $status = $readWriteOk ? 'ok' : 'error';

        if ($readWriteOk && app()->environment('production') && ! $configCached) {
            $status = 'warning';
        }

        $message = match (true) {
            ! $readWriteOk => 'Cache kunde inte läsas eller skrivas.',
            $status === 'warning' => 'Cache fungerar, men config är inte cachad i production.',
            default => 'Cache svarar som den ska.',
        };

        return $this->rich('cache', $status, 'Cache', $message, $items);
    }

    /**
     * @return array{key: string, status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkQueue(): array
    {
        $driver = (string) config('queue.default');

        if ($driver === 'sync') {
            return $this->rich(
                'queue',
                'ok',
                'Jobbkö',
                'QUEUE_CONNECTION=sync — inget separat queue-worker behövs.',
                ['Driver' => $driver]
            );
        }

        if ($driver !== 'database') {
            return $this->rich(
                'queue',
                'ok',
                'Jobbkö',
                "Driver {$driver} — kontrollera att worker/processer körs för denna kö.",
                ['Driver' => $driver]
            );
        }

        if (! Schema::hasTable('jobs')) {
            return $this->rich(
                'queue',
                'error',
                'Jobbkö',
                'Tabellen jobs saknas. Kör php artisan migrate --force (queue:table).',
                ['Driver' => $driver, 'jobs-tabell' => 'Saknas']
            );
        }

        try {
            $pending = (int) DB::table('jobs')->count();
            $failed = Schema::hasTable('failed_jobs')
                ? (int) DB::table('failed_jobs')->count()
                : null;

            $oldestAvailableAt = DB::table('jobs')->min('available_at');
            $oldestLabel = '—';

            if ($oldestAvailableAt !== null) {
                $oldestLabel = Carbon::createFromTimestamp((int) $oldestAvailableAt)
                    ->timezone(config('app.timezone'))
                    ->format('Y-m-d H:i:s');
            }

            $items = [
                'Driver' => $driver,
                'Väntande jobb' => (string) $pending,
                'Misslyckade jobb' => $failed !== null ? (string) $failed : 'Tabell saknas',
                'Äldsta köade' => $oldestLabel,
            ];

            $status = 'ok';
            $message = 'Jobbkön ser normal ut.';

            if ($failed !== null && $failed > 0) {
                $status = 'warning';
                $message = "{$failed} misslyckat jobb i failed_jobs.";
            }

            if ($pending >= 50) {
                $status = 'warning';
                $message = "{$pending} väntande jobb — kontrollera att queue:work körs.";
            }

            if ($pending >= 200) {
                $status = 'error';
                $message = "{$pending} väntande jobb — kön verkar inte bearbetas.";
            }

            return $this->rich('queue', $status, 'Jobbkö', $message, $items);
        } catch (\Throwable $exception) {
            return $this->rich(
                'queue',
                'error',
                'Jobbkö',
                'Kunde inte läsa jobbkön.',
                ['Driver' => $driver, 'Fel' => $exception->getMessage()]
            );
        }
    }

    /**
     * @return array{key: string, status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkMail(): array
    {
        $mailer = (string) config('mail.default');
        $host = (string) config('mail.mailers.smtp.host');
        $from = (string) config('mail.from.address');

        $status = ($mailer !== '' && $from !== '') ? 'ok' : 'warning';

        return $this->rich(
            'mail',
            $status,
            'E-post',
            $status === 'ok'
                ? 'E-postkonfiguration finns.'
                : 'E-postkonfiguration verkar ofullständig.',
            [
                'Mailer' => $mailer !== '' ? $mailer : 'Saknas',
                'SMTP host' => $host !== '' ? $host : 'Ej satt / ej SMTP',
                'Från-adress' => $from !== '' ? $from : 'Saknas',
            ]
        );
    }

    /**
     * @return array{key: string, status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkScheduler(): array
    {
        $path = storage_path('app/scheduler-heartbeat.json');

        if (! file_exists($path)) {
            return $this->rich(
                'scheduler',
                'warning',
                'Scheduler / Cron',
                'Laravel scheduler verkar inte ha kört ännu. Kontrollera cron-jobbet.',
                [
                    'Status' => 'Ingen heartbeat hittad',
                    'Sökväg' => $path,
                ]
            );
        }

        try {
            $data = json_decode((string) file_get_contents($path), true);
            $ranAt = isset($data['ran_at']) ? Carbon::parse($data['ran_at']) : null;

            if (! $ranAt) {
                throw new \RuntimeException('Ogiltig heartbeat.');
            }

            $ageSeconds = $ranAt->diffInSeconds(now());
            $status = $ageSeconds <= 180 ? 'ok' : 'warning';

            return $this->rich(
                'scheduler',
                $status,
                'Scheduler / Cron',
                $status === 'ok'
                    ? 'Laravel scheduler körs som den ska.'
                    : 'Schedulern har inte uppdaterat heartbeat på över 3 minuter.',
                [
                    'Senaste körning' => $ranAt->format('Y-m-d H:i:s'),
                    'Ålder' => round($ageSeconds / 60, 1).' minuter',
                ]
            );
        } catch (\Throwable $exception) {
            return $this->rich(
                'scheduler',
                'error',
                'Scheduler / Cron',
                'Heartbeat-filen kunde inte läsas.',
                ['Fel' => $exception->getMessage()]
            );
        }
    }

    /**
     * @return array{key: string, status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkLogs(): array
    {
        $logFile = storage_path('logs/laravel.log');
        $exists = File::exists($logFile);

        if (! $exists) {
            return $this->rich(
                'logs',
                'warning',
                'Loggar',
                'Ingen Laravel-loggfil hittades ännu.',
                ['laravel.log' => 'Saknas']
            );
        }

        $size = File::size($logFile);
        $errorsLast24h = $this->countLogErrorsSince($logFile, now()->subDay());
        $tailErrors = $this->countLogErrorsInTail($logFile);

        $status = $errorsLast24h > 0 ? 'warning' : 'ok';

        if ($errorsLast24h >= 25) {
            $status = 'error';
        }

        return $this->rich(
            'logs',
            $status,
            'Loggar',
            $errorsLast24h > 0
                ? "{$errorsLast24h} felrad(er) senaste 24 timmarna."
                : 'Inga felrader i loggen senaste 24 timmarna.',
            [
                'laravel.log' => 'Finns',
                'Storlek' => $this->formatBytes($size),
                'Senast ändrad' => date('Y-m-d H:i', File::lastModified($logFile)),
                'Fel (24 h)' => (string) $errorsLast24h,
                'Fel (slutet av fil)' => (string) $tailErrors,
            ]
        );
    }

    /**
     * @return array{key: string, status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkHttpHealth(): array
    {
        $url = url('/up');

        try {
            $response = app()->handle(Request::create('/up', 'GET'));
            $status = $response->getStatusCode();
        } catch (\Throwable $exception) {
            return $this->rich(
                'http',
                'error',
                'HTTP /up',
                'Hälsokontrollen svarade inte.',
                ['URL' => $url, 'Fel' => $exception->getMessage()]
            );
        }

        if ($status >= 200 && $status < 300) {
            return $this->rich(
                'http',
                'ok',
                'HTTP /up',
                'Applikationens hälsosvar är OK.',
                ['URL' => $url, 'Status' => (string) $status]
            );
        }

        return $this->rich(
            'http',
            'error',
            'HTTP /up',
            'Hälsosvar returnerade oväntad status.',
            ['URL' => $url, 'Status' => (string) $status]
        );
    }

    /**
     * @return list<array{name: string, status: string, message: string}>
     */
    private function httpDeployChecks(string $baseUrl): array
    {
        return [
            $this->flatFromRich($this->checkHttpRoute($baseUrl, '/up', 'HTTP /up'), 'HTTP /up'),
            $this->flatFromRich($this->checkHttpRoute($baseUrl, '/login', 'HTTP /login'), 'HTTP /login'),
        ];
    }

    /**
     * @return array{key: string, status: string, title: string, message: string, items: array<string, string>}
     */
    private function checkHttpRoute(string $baseUrl, string $path, string $title): array
    {
        $url = $baseUrl.$path;

        try {
            $response = Http::timeout(15)
                ->withOptions(['verify' => ! app()->environment('local', 'testing')])
                ->get($url);
        } catch (\Throwable $exception) {
            return $this->rich(
                'http_route',
                'error',
                $title,
                "{$url} — ".$exception->getMessage(),
                ['URL' => $url]
            );
        }

        if ($response->successful()) {
            return $this->rich(
                'http_route',
                'ok',
                $title,
                "{$url} svarade {$response->status()}.",
                ['URL' => $url, 'Status' => (string) $response->status()]
            );
        }

        return $this->rich(
            'http_route',
            'error',
            $title,
            "{$url} svarade {$response->status()} (förväntat 2xx).",
            ['URL' => $url, 'Status' => (string) $response->status()]
        );
    }

    /**
     * @return array{label: string, status: string}
     */
    private function diskSpaceSummary(string $path): array
    {
        $free = @disk_free_space($path);
        $total = @disk_total_space($path);

        if ($free === false || $total === false || $total <= 0) {
            return [
                'label' => 'Kunde inte mätas',
                'status' => 'ok',
            ];
        }

        $freePercent = ($free / $total) * 100;

        return [
            'label' => $this->formatBytes((int) $free).' ledigt ('.round($freePercent, 1).' %)',
            'status' => $freePercent < 10 ? 'warning' : 'ok',
        ];
    }

    private function countLogErrorsInTail(string $logFile): int
    {
        $chunk = $this->readLogTail($logFile, 65536);

        if ($chunk === '') {
            return 0;
        }

        preg_match_all('/\]\s+\w+\.(ERROR|CRITICAL|ALERT|EMERGENCY):/', $chunk, $matches);

        return count($matches[0]);
    }

    private function countLogErrorsSince(string $logFile, Carbon $since): int
    {
        $chunk = $this->readLogTail($logFile, 524288);

        if ($chunk === '') {
            return 0;
        }

        $count = 0;

        foreach (explode("\n", $chunk) as $line) {
            if (! preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                continue;
            }

            $loggedAt = Carbon::parse($matches[1], config('app.timezone'));

            if ($loggedAt->lt($since)) {
                continue;
            }

            if (preg_match('/\]\s+\w+\.(ERROR|CRITICAL|ALERT|EMERGENCY):/', $line)) {
                $count++;
            }
        }

        return $count;
    }

    private function readLogTail(string $logFile, int $maxBytes): string
    {
        $size = File::size($logFile);

        if ($size === 0) {
            return '';
        }

        $handle = fopen($logFile, 'rb');

        if ($handle === false) {
            return '';
        }

        $readSize = (int) min($size, $maxBytes);
        fseek($handle, -$readSize, SEEK_END);
        $chunk = (string) fread($handle, $readSize);
        fclose($handle);

        return $chunk;
    }

    private function worstStatus(string ...$statuses): string
    {
        if (in_array('error', $statuses, true)) {
            return 'error';
        }

        if (in_array('warning', $statuses, true)) {
            return 'warning';
        }

        return 'ok';
    }

    /**
     * @param  array<string, string>  $items
     * @param  list<array{title: string, items: array<string, string>}>  $groups
     * @return array{key: string, status: string, title: string, message: string, items: array<string, string>, groups: list<array{title: string, items: array<string, string>}>}
     */
    private function rich(string $key, string $status, string $title, string $message, array $items = [], array $groups = []): array
    {
        if ($groups === [] && $items !== []) {
            $groups = [
                ['title' => 'Detaljer', 'items' => $items],
            ];
        }

        return [
            'key' => $key,
            'status' => $status,
            'title' => $title,
            'message' => $message,
            'items' => $items,
            'groups' => $groups,
        ];
    }

    private function formatMigrationLabel(string $migration): string
    {
        return str_replace('_', ' ', $migration);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }
}
