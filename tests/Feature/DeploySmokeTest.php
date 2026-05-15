<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DeploySmokeTest extends TestCase
{
    public function test_health_endpoint_responds(): void
    {
        $this->get('/up')
            ->assertOk();
    }

    public function test_login_page_responds(): void
    {
        $this->get(route('login'))
            ->assertOk();
    }

    public function test_deploy_smoke_artisan_command_succeeds_in_testing(): void
    {
        $this->artisan('deploy:smoke --strict')
            ->assertSuccessful();
    }

    public function test_deploy_smoke_http_checks_use_configured_url(): void
    {
        Http::fake([
            'https://staging.example.test/up' => Http::response('ok', 200),
            'https://staging.example.test/login' => Http::response('<html></html>', 200),
        ]);

        $this->artisan('deploy:smoke --url=https://staging.example.test --strict')
            ->assertSuccessful();

        Http::assertSentCount(2);
    }

    public function test_deploy_smoke_fails_when_http_endpoint_errors(): void
    {
        Http::fake([
            'https://staging.example.test/up' => Http::response('fail', 503),
            'https://staging.example.test/login' => Http::response('<html></html>', 200),
        ]);

        $this->artisan('deploy:smoke --url=https://staging.example.test --strict')
            ->assertFailed();
    }
}
