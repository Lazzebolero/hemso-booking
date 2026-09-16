<?php

namespace App\Http\Controllers;

use App\Services\PublicSiteStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class PublicSiteStatusController extends Controller
{
    public function __construct(
        private PublicSiteStatusService $siteStatusService,
    ) {}

    public function json(): JsonResponse
    {
        $payload = $this->siteStatusService->status();

        return response()
            ->json([
                'data' => $payload,
                'meta' => [
                    'generated_at' => $payload['generated_at'] ?? now()->toIso8601String(),
                ],
            ])
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'public, max-age=60');
    }

    public function widgetScript(): Response
    {
        $path = public_path('js/site-status-bar.js');

        abort_unless(File::isFile($path), 404);

        return response(File::get($path), 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
