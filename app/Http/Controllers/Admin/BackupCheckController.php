<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class BackupCheckController extends Controller
{
    public function index(): View
    {
        $data = $this->readData();

        $lastCheckedAt = $data['last_checked_at'] ?? null;
        $lastRestoreTestAt = $data['last_restore_test_at'] ?? null;

        $status = 'warning';
        $message = 'Ingen backupkontroll är registrerad.';

        if ($lastCheckedAt) {
            $ageHours = now()->diffInHours(\Carbon\Carbon::parse($lastCheckedAt));

            if ($ageHours <= 48) {
                $status = 'ok';
                $message = 'Backup har kontrollerats nyligen.';
            } elseif ($ageHours <= 168) {
                $status = 'warning';
                $message = 'Backup är kontrollerad, men inte de senaste 48 timmarna.';
            } else {
                $status = 'error';
                $message = 'Backupkontrollen är äldre än 7 dagar.';
            }
        }

        return view('admin.backup-check.index', [
            'data' => $data,
            'status' => $status,
            'message' => $message,
            'lastCheckedAt' => $lastCheckedAt,
            'lastRestoreTestAt' => $lastRestoreTestAt,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
            'mark_restore_tested' => ['nullable', 'boolean'],
        ]);

        $data = $this->readData();

        $data['last_checked_at'] = now()->toIso8601String();
        $data['last_checked_by'] = auth()->user()?->name;
        $data['last_note'] = $validated['note'] ?? null;

        if ($request->boolean('mark_restore_tested')) {
            $data['last_restore_test_at'] = now()->toIso8601String();
            $data['last_restore_test_by'] = auth()->user()?->name;
        }

        $this->writeData($data);

        return redirect()
            ->route('admin.backup-check.index')
            ->with('success', 'Backupkontroll sparad.');
    }

    private function path(): string
    {
        return storage_path('app/backup-check.json');
    }

    private function readData(): array
    {
        if (! File::exists($this->path())) {
            return [];
        }

        return json_decode(File::get($this->path()), true) ?: [];
    }

    private function writeData(array $data): void
    {
        File::ensureDirectoryExists(dirname($this->path()));

        File::put($this->path(), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}