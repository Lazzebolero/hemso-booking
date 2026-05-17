<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PhotoDiagnosticsController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.photo-diagnostics.index', [
            'files' => $this->files(),
            'opcache' => $this->opcache(),
            'dogInput' => $this->extractSnippet(resource_path('views/visitor-dogs/_form.blade.php'), 'name="photo"'),
            'reportInput' => $this->extractSnippet(resource_path('views/guide/report-form.blade.php'), 'name="attachment"'),
        ]);
    }

    /**
     * @return array<int, array{path: string, absolute_path: string, exists: bool, expected_sha1: string, actual_sha1: ?string, matches_expected: bool, modified_at: ?string, opcache_cached: ?bool}>
     */
    private function files(): array
    {
        $expectedHashes = [
            'resources/views/visitor-dogs/_form.blade.php' => '202474b9314e4386a61af0e03af82ee76c254fc2',
            'resources/views/visitor-dogs/guide-form.blade.php' => 'e4d60c64b65edd6d7477a5fef77b1fc96f72ae8a',
            'resources/views/visitor-dogs/host-form.blade.php' => 'd25ddf6fe60de4e562c69476b08eeac46cc87ae9',
            'app/Http/Controllers/VisitorDogController.php' => 'af677150d3080a33bef943af5d0784a6983de215',
            'app/Http/Requests/StoreVisitorDogRequest.php' => 'c43b966e08a33f0d56c000418a10c341b330b678',
            'app/Support/VisitorDogSupport.php' => '01a2a7983226a7bf11f5c366f89e95e44489e213',
            'resources/views/guide/report-form.blade.php' => 'd3de0811a881f9b58d37ac8d963502748fcc1194',
            'app/Http/Controllers/Guide/FacilityReportController.php' => '8162e7c9a819e4f7c8d17928dd66638196a26350',
            'resources/views/layouts/app.blade.php' => 'ebf5d3fd6eaf3cb786e6fea81c198cf140365c69',
            'resources/views/layouts/guide.blade.php' => '7f88f24e9b6a22e344b3b72acb123843932418cb',
            'resources/views/partials/pwa.blade.php' => '7be190df3b652aed0a51b880c4c9749453de41e6',
            'public/service-worker.js' => 'b96dadbaf50ad52debb21afb5909884da387bb8f',
            'public/js/offline-queue.js' => '64dae0e1b050f625a7a53c7dca823c67894c9d7c',
            'routes/visitor-dogs.php' => '73d742771998a6bc71b1e8da4c7d8efe48a06935',
            'routes/guide.php' => 'cd5f77c3efe7e288d4684a9a29ce4dec22d285a5',
            'routes/pwa.php' => '9b947218135349fea0545fcc017e27d63376675b',
            'routes/web.php' => '965f4c142404873a3b71033c2b5a27da9f9c91c6',
        ];

        return collect($expectedHashes)
            ->map(function (string $expectedSha1, string $relativePath): array {
                $absolutePath = base_path($relativePath);
                $exists = is_file($absolutePath);
                $actualSha1 = $exists ? sha1_file($absolutePath) : null;

                return [
                    'path' => $relativePath,
                    'absolute_path' => $absolutePath,
                    'exists' => $exists,
                    'expected_sha1' => $expectedSha1,
                    'actual_sha1' => $actualSha1,
                    'matches_expected' => $actualSha1 === $expectedSha1,
                    'modified_at' => $exists ? date('Y-m-d H:i:s', (int) filemtime($absolutePath)) : null,
                    'opcache_cached' => $exists ? $this->isOpcacheCached($absolutePath) : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{available: bool, enabled: ?bool, validate_timestamps: ?bool, revalidate_freq: ?string, memory_consumption: ?array<string, mixed>}
     */
    private function opcache(): array
    {
        if (! function_exists('opcache_get_status')) {
            return [
                'available' => false,
                'enabled' => null,
                'validate_timestamps' => null,
                'revalidate_freq' => null,
                'memory_consumption' => null,
            ];
        }

        /** @var array<string, mixed>|false $status */
        $status = opcache_get_status(false);

        return [
            'available' => true,
            'enabled' => is_array($status) ? (bool) ($status['opcache_enabled'] ?? false) : null,
            'validate_timestamps' => filter_var(ini_get('opcache.validate_timestamps'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE),
            'revalidate_freq' => ini_get('opcache.revalidate_freq') ?: null,
            'memory_consumption' => is_array($status) && isset($status['memory_usage']) && is_array($status['memory_usage'])
                ? $status['memory_usage']
                : null,
        ];
    }

    private function isOpcacheCached(string $absolutePath): ?bool
    {
        if (! function_exists('opcache_is_script_cached')) {
            return null;
        }

        return opcache_is_script_cached($absolutePath);
    }

    private function extractSnippet(string $absolutePath, string $needle): string
    {
        if (! is_file($absolutePath)) {
            return 'File not found: '.$absolutePath;
        }

        $lines = file($absolutePath, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return 'Could not read: '.$absolutePath;
        }

        $matchIndex = collect($lines)->search(fn (string $line): bool => Str::contains($line, $needle));
        if ($matchIndex === false) {
            return 'Needle not found: '.$needle;
        }

        $start = max(0, ((int) $matchIndex) - 4);
        $slice = array_slice($lines, $start, 10, true);

        return collect($slice)
            ->map(fn (string $line, int $index): string => str_pad((string) ($index + 1), 4, ' ', STR_PAD_LEFT).' | '.$line)
            ->implode("\n");
    }
}
