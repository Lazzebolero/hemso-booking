<?php

namespace Tests\Unit;

use App\Support\SystemHealthChecker;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class SystemHealthCheckerTest extends TestCase
{
    public function test_application_check_uses_grouped_details(): void
    {
        $check = app(SystemHealthChecker::class)->dashboardChecks()['app'];

        $this->assertArrayHasKey('groups', $check);
        $this->assertCount(2, $check['groups']);
        $this->assertSame('Säkerhet', $check['groups'][0]['title']);
        $this->assertSame('Miljö & version', $check['groups'][1]['title']);
    }

    public function test_migrations_check_exposes_groups_for_details(): void
    {
        $check = app(SystemHealthChecker::class)->dashboardChecks()['migrations'];

        $this->assertArrayHasKey('groups', $check);
        $this->assertNotEmpty($check['groups']);
    }

    public function test_queue_check_is_ok_for_sync_driver(): void
    {
        Config::set('queue.default', 'sync');

        $check = app(SystemHealthChecker::class)->dashboardChecks()['queue'];

        $this->assertSame('ok', $check['status']);
        $this->assertSame('sync', $check['items']['Driver']);
    }

    public function test_queue_check_warns_when_failed_jobs_exist(): void
    {
        Config::set('queue.default', 'database');

        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Test failure',
            'failed_at' => now(),
        ]);

        $check = app(SystemHealthChecker::class)->dashboardChecks()['queue'];

        $this->assertSame('warning', $check['status']);
        $this->assertSame('1', $check['items']['Misslyckade jobb']);
    }

    public function test_logs_check_counts_errors_in_last_24_hours(): void
    {
        $logFile = storage_path('logs/laravel.log');
        $backup = $logFile.'.bak';

        if (File::exists($logFile)) {
            File::move($logFile, $backup);
        }

        try {
            $timestamp = now()->format('Y-m-d H:i:s');
            File::put($logFile, "[{$timestamp}] testing.ERROR: Something went wrong\n");

            $check = app(SystemHealthChecker::class)->dashboardChecks()['logs'];

            $this->assertSame('warning', $check['status']);
            $this->assertSame('1', $check['items']['Fel (24 h)']);
        } finally {
            File::delete($logFile);

            if (File::exists($backup)) {
                File::move($backup, $logFile);
            }
        }
    }
}
