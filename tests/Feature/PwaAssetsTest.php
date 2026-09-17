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

    public function test_guide_tour_optimistic_ui_script_is_served(): void
    {
        $this->get('/js/guide-tour-optimistic-ui.js')
            ->assertOk()
            ->assertSee('hemso-guide-tour-pending:', false);
    }

    public function test_offline_queue_exposes_public_enqueue_api(): void
    {
        $path = resource_path('js/pwa-offline-queue.js');
        $this->assertFileExists($path);
        $content = (string) file_get_contents($path);
        $this->assertStringContainsString('window.hemsoOfflineQueue', $content);
        $this->assertStringContainsString('enqueueForm', $content);
    }

    public function test_offline_queue_reloads_page_after_successful_sync(): void
    {
        $path = resource_path('js/pwa-offline-queue.js');
        $this->assertFileExists($path);
        $content = (string) file_get_contents($path);
        $this->assertStringContainsString('location.reload', $content);
        $this->assertStringContainsString('applyFlushResult', $content);
        $this->assertStringContainsString('skipReload', $content);
        $this->assertStringContainsString('refreshSessionContext', $content);
        $this->assertStringContainsString('dedupeQueueBeforeFlush', $content);
        $this->assertStringContainsString('skipPendingQueueItems', $content);
    }

    public function test_offline_queue_handles_auth_failures_without_blind_reload(): void
    {
        $path = resource_path('js/pwa-offline-queue.js');
        $this->assertFileExists($path);
        $content = (string) file_get_contents($path);
        $this->assertStringContainsString('isAuthFailure', $content);
        $this->assertStringContainsString('Inloggningen har g\u00e5tt ut', $content);
    }

    public function test_service_worker_avoids_caching_redirect_and_error_html(): void
    {
        $path = public_path('service-worker.js');
        $this->assertFileExists($path);
        $content = (string) file_get_contents($path);

        $this->assertStringContainsString('hemso-pwa-v24', $content);
        $this->assertStringContainsString('isGuideTourShowPath', $content);
        $this->assertStringContainsString('isGuideShellOfflinePath', $content);
        $this->assertStringContainsString('matchCachedPathSuffix', $content);
        $this->assertStringContainsString('warm-html-cache', $content);
        $this->assertStringContainsString('canonicalHtmlRequest', $content);
        $this->assertStringContainsString('respondWithHtml', $content);
        $this->assertStringContainsString('invalidate-html-cache', $content);
        $this->assertStringNotContainsString("absUrl('/')", $content);
        $this->assertStringContainsString('!isNavigate && !wantsHtml', $content);
        $this->assertStringContainsString('!isNavigate && !wantsHtml', $content);
        $this->assertStringContainsString('ignoreVary', $content);
        $this->assertStringContainsString('.redirected', $content);
        $this->assertStringContainsString('cacheIfEligible', $content);
        $this->assertStringContainsString('isDynamicAppPage', $content);
        $this->assertStringContainsString('isLiveSystemMessageRequest', $content);
    }
}
