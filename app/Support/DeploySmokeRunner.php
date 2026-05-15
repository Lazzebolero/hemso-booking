<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class DeploySmokeRunner
{
    /**
     * @return list<array{name: string, status: string, message: string}>
     */
    public function run(?string $baseUrl = null): array
    {
        $checks = [
            $this->checkAppKey(),
            $this->checkEnvironment(),
            $this->checkDatabase(),
            $this->checkMigrations(),
            $this->checkStorage(),
            $this->checkFrontendBuild(),
        ];

        if ($baseUrl !== null && $baseUrl !== '') {
            $checks = array_merge($checks, $this->checkHttpEndpoints(rtrim($baseUrl, '/')));
        }

        return $checks;
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkAppKey(): array
    {
        $key = (string) config('app.key');

        if ($key === '') {
            return $this->result('APP_KEY', 'error', 'APP_KEY saknas. Kör php artisan key:generate.');
        }

        return $this->result('APP_KEY', 'ok', 'Applikationsnyckel är satt.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkEnvironment(): array
    {
        $environment = app()->environment();
        $debug = (bool) config('app.debug');

        if ($environment === 'testing') {
            return $this->result('Miljö', 'ok', "Miljö: {$environment} (automatiskt test).");
        }

        if ($environment === 'local') {
            return $this->result(
                'Miljö',
                'ok',
                $debug
                    ? 'Miljö: local, APP_DEBUG på (förväntat vid lokal utveckling).'
                    : 'Miljö: local, APP_DEBUG av.'
            );
        }

        if ($environment === 'production' && $debug) {
            return $this->result('Miljö', 'error', 'APP_DEBUG är på i production.');
        }

        if ($debug) {
            return $this->result('Miljö', 'warning', "Miljö: {$environment}, APP_DEBUG är på.");
        }

        return $this->result('Miljö', 'ok', "Miljö: {$environment}, APP_DEBUG av.");
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $exception) {
            return $this->result('Databas', 'error', 'Databasen svarar inte: '.$exception->getMessage());
        }

        $requiredTables = [
            'users',
            'roles',
            'bookings',
            'tours',
            'visitor_dogs',
            'sessions',
        ];

        $missing = array_values(array_filter(
            $requiredTables,
            fn (string $table): bool => ! Schema::hasTable($table)
        ));

        if ($missing !== []) {
            return $this->result(
                'Databas',
                'error',
                'Saknade tabeller: '.implode(', ', $missing).'. Kör php artisan migrate --force.'
            );
        }

        return $this->result('Databas', 'ok', 'Databasen svarar och kritiska tabeller finns.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkMigrations(): array
    {
        try {
            $migrator = app('migrator');
            $files = $migrator->getMigrationFiles([database_path('migrations')]);
            $ran = $migrator->getRepository()->getRan();
            $pending = count(array_diff(array_keys($files), $ran));
        } catch (\Throwable $exception) {
            return $this->result('Migrationer', 'error', 'Kunde inte läsa migrationsstatus: '.$exception->getMessage());
        }

        if ($pending > 0) {
            return $this->result(
                'Migrationer',
                'error',
                "{$pending} väntande migration(er). Kör php artisan migrate --force."
            );
        }

        return $this->result('Migrationer', 'ok', 'Alla migrationer är körda.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkStorage(): array
    {
        $issues = [];

        if (! is_writable(storage_path())) {
            $issues[] = 'storage är inte skrivbar';
        }

        if (! is_writable(base_path('bootstrap/cache'))) {
            $issues[] = 'bootstrap/cache är inte skrivbar';
        }

        if (! File::exists(public_path('storage'))) {
            $issues[] = 'public/storage saknas (kör php artisan storage:link)';
        }

        if ($issues !== []) {
            return $this->result('Storage', 'error', implode('; ', $issues).'.');
        }

        return $this->result('Storage', 'ok', 'Storage, cache och public/storage ser bra ut.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkFrontendBuild(): array
    {
        $manifestPath = public_path('build/manifest.json');

        if (File::exists($manifestPath)) {
            return $this->result('Frontend (Vite)', 'ok', 'build/manifest.json finns.');
        }

        if (app()->environment('testing')) {
            return $this->result(
                'Frontend (Vite)',
                'ok',
                'build/manifest.json saknas (ignoreras i testing).'
            );
        }

        if (app()->environment('local')) {
            return $this->result(
                'Frontend (Vite)',
                'warning',
                'build/manifest.json saknas (OK om du kör npm run dev).'
            );
        }

        return $this->result(
            'Frontend (Vite)',
            'error',
            'build/manifest.json saknas. Kör npm ci && npm run build före deploy.'
        );
    }

    /**
     * @return list<array{name: string, status: string, message: string}>
     */
    private function checkHttpEndpoints(string $baseUrl): array
    {
        return [
            $this->checkHttpRoute($baseUrl, '/up', 'HTTP /up'),
            $this->checkHttpRoute($baseUrl, '/login', 'HTTP /login'),
        ];
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkHttpRoute(string $baseUrl, string $path, string $name): array
    {
        $url = $baseUrl.$path;

        try {
            $response = Http::timeout(15)
                ->withOptions(['verify' => ! app()->environment('local', 'testing')])
                ->get($url);
        } catch (\Throwable $exception) {
            return $this->result($name, 'error', "{$url} — ".$exception->getMessage());
        }

        if ($response->successful()) {
            return $this->result($name, 'ok', "{$url} svarade {$response->status()}.");
        }

        return $this->result(
            $name,
            'error',
            "{$url} svarade {$response->status()} (förväntat 2xx)."
        );
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function result(string $name, string $status, string $message): array
    {
        return [
            'name' => $name,
            'status' => $status,
            'message' => $message,
        ];
    }
}
