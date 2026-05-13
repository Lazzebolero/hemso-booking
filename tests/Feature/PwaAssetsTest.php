<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaAssetsTest extends TestCase
{
    public function test_pwa_manifest_is_served(): void
    {
        $this->get('/manifest.webmanifest')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json; charset=UTF-8')
            ->assertJsonPath('name', 'Hemsö Fästning Bokning')
            ->assertJsonPath('start_url', url('/'))
            ->assertJsonPath('scope', url('/'));
    }

    public function test_service_worker_is_served(): void
    {
        $this->get('/service-worker.js')
            ->assertOk();
    }

    public function test_offline_fallback_is_served(): void
    {
        $this->get('/offline.html')
            ->assertOk();
    }

    public function test_offline_queue_script_is_served(): void
    {
        $this->get('/js/offline-queue.js')
            ->assertOk()
            ->assertSee('request_queue', false);
    }

    public function test_offline_queue_reloads_page_after_successful_sync(): void
    {
        $path = resource_path('js/pwa-offline-queue.js');
        $this->assertFileExists($path);
        $content = (string) file_get_contents($path);
        $this->assertStringContainsString('location.reload', $content);
        $this->assertStringContainsString('flushQueue', $content);
    }

    public function test_service_worker_avoids_caching_redirect_and_error_html(): void
    {
        $path = public_path('service-worker.js');
        $this->assertFileExists($path);
        $content = (string) file_get_contents($path);

        $this->assertStringContainsString('hemso-pwa-v11', $content);
        $this->assertStringContainsString('ignoreVary', $content);
        $this->assertStringContainsString('.redirected', $content);
        $this->assertStringContainsString('cacheIfEligible', $content);
    }
}

