<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class SystemLogController extends Controller
{
    public function index(Request $request): View
    {
        $lines = (int) $request->get('lines', 200);
        $lines = max(50, min($lines, 1000));

        $logFile = storage_path('logs/laravel.log');

        $exists = File::exists($logFile);
        $size = $exists ? File::size($logFile) : 0;
        $modified = $exists ? date('Y-m-d H:i:s', File::lastModified($logFile)) : null;

        $content = $exists
            ? $this->tailFile($logFile, $lines)
            : '';

        return view('admin.system-logs.index', [
            'exists' => $exists,
            'size' => $size,
            'modified' => $modified,
            'content' => $content,
            'lines' => $lines,
        ]);
    }

    private function tailFile(string $path, int $lines): string
    {
        if (! is_readable($path)) {
            return 'Loggfilen finns men kan inte läsas.';
        }

        $file = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);

        $lastLine = $file->key();
        $startLine = max(0, $lastLine - $lines);

        $output = [];

        $file->seek($startLine);

        while (! $file->eof()) {
            $output[] = $file->current();
            $file->next();
        }

        return trim(implode('', $output));
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}