<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class PhotoDiagnosticsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = [
            'Photo diagnostics',
            'Generated: '.date('Y-m-d H:i:s'),
            '',
            'Files:',
        ];

        foreach ($this->files() as $file) {
            $lines[] = sprintf(
                '%s | %s | actual=%s | expected=%s | modified=%s',
                $file['matches_expected'] ? 'OK' : 'DIFF',
                $file['path'],
                $file['actual_sha1'] ?? 'missing',
                $file['expected_sha1'],
                $file['modified_at'] ?? '-',
            );
        }

        $lines[] = '';
        $lines[] = 'Dog input:';
        $lines[] = $this->extractSnippet(resource_path('views/visitor-dogs/_form.blade.php'), 'name="photo"');
        $lines[] = '';
        $lines[] = 'Report input:';
        $lines[] = $this->extractSnippet(resource_path('views/guide/report-form.blade.php'), 'name="attachment"');

        return response(implode("\n", $lines), 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /**
     * @return array<int, array{path: string, expected_sha1: string, actual_sha1: ?string, matches_expected: bool, modified_at: ?string}>
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

        $files = [];

        foreach ($expectedHashes as $relativePath => $expectedSha1) {
            $absolutePath = base_path($relativePath);
            $exists = is_file($absolutePath);
            $actualSha1 = $exists ? sha1_file($absolutePath) : null;

            $files[] = [
                'path' => $relativePath,
                'expected_sha1' => $expectedSha1,
                'actual_sha1' => $actualSha1,
                'matches_expected' => $actualSha1 === $expectedSha1,
                'modified_at' => $exists ? date('Y-m-d H:i:s', (int) filemtime($absolutePath)) : null,
            ];
        }

        return $files;
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

        $matchIndex = null;
        foreach ($lines as $index => $line) {
            if (str_contains($line, $needle)) {
                $matchIndex = $index;
                break;
            }
        }

        if ($matchIndex === null) {
            return 'Needle not found: '.$needle;
        }

        $start = max(0, $matchIndex - 4);
        $slice = array_slice($lines, $start, 10, true);
        $snippet = [];

        foreach ($slice as $index => $line) {
            $snippet[] = str_pad((string) ($index + 1), 4, ' ', STR_PAD_LEFT).' | '.$line;
        }

        return implode("\n", $snippet);
    }
}
