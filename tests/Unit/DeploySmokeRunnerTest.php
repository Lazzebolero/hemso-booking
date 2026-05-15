<?php

namespace Tests\Unit;

use App\Support\DeploySmokeRunner;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeploySmokeRunnerTest extends TestCase
{
    public function test_runner_reports_database_ok_when_tables_exist(): void
    {
        $checks = app(DeploySmokeRunner::class)->run();

        $database = collect($checks)->firstWhere('name', 'Databas');

        $this->assertNotNull($database);
        $this->assertSame('ok', $database['status']);
        $this->assertTrue(Schema::hasTable('visitor_dogs'));
    }

    public function test_runner_reports_error_when_app_key_missing(): void
    {
        Config::set('app.key', '');

        $checks = app(DeploySmokeRunner::class)->run();

        $appKey = collect($checks)->firstWhere('name', 'APP_KEY');

        $this->assertNotNull($appKey);
        $this->assertSame('error', $appKey['status']);
    }

    public function test_runner_treats_debug_as_ok_in_local_environment(): void
    {
        $this->app['env'] = 'local';
        Config::set('app.debug', true);

        $checks = app(DeploySmokeRunner::class)->run();

        $environment = collect($checks)->firstWhere('name', 'Miljö');

        $this->assertNotNull($environment);
        $this->assertSame('ok', $environment['status']);
    }

    public function test_runner_warns_when_debug_on_in_staging_environment(): void
    {
        $this->app['env'] = 'staging';
        Config::set('app.debug', true);

        $checks = app(DeploySmokeRunner::class)->run();

        $environment = collect($checks)->firstWhere('name', 'Miljö');

        $this->assertNotNull($environment);
        $this->assertSame('warning', $environment['status']);
    }
}
