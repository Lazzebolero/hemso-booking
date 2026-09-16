<?php

namespace App\Http\Controllers;

use App\Services\PublicSiteStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class PublicSiteStatusController extends Controller
{
    public function json(): JsonResponse
    {
        try {
            $payload = app(PublicSiteStatusService::class)->status();
            $degraded = false;
        } catch (\Throwable $exception) {
            report($exception);
            $payload = $this->fallbackPayload();
            $degraded = true;
        }

        return response()
            ->json([
                'data' => $payload,
                'meta' => [
                    'generated_at' => $payload['generated_at'] ?? now()->toIso8601String(),
                    'degraded' => $degraded,
                    'error' => null,
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

    /**
     * @return array{
     *     generated_at: string,
     *     weather: array<string, mixed>,
     *     ferry: array<string, mixed>
     * }
     */
    private function fallbackPayload(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'weather' => [
                'available' => false,
                'temperature_c' => null,
                'temperature_label' => null,
                'summary' => null,
                'symbol_label' => null,
                'wind_label' => null,
                'station_name' => null,
                'text' => 'Väderdata saknas just nu',
            ],
            'ferry' => [
                'available' => false,
                'direction' => 'to_island',
                'direction_label' => 'Till Hemsön',
                'from_label' => 'Strinningen',
                'time' => null,
                'minutes_until' => null,
                'status_label' => null,
                'delay_minutes' => null,
                'date' => null,
                'is_today' => true,
                'text' => 'Färjedata saknas just nu',
            ],
        ];
    }
}
