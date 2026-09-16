<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class SchedulerFailureStore
{
    /**
     * @var list<string>
     */
    private const TRACKED_COMMANDS = [
        'bookings:send-reminders',
        'ferry:sync-traffic',
        'queue:work',
        'security:check-login-alerts',
        'system-health:send-daily-report',
        'tours:auto-complete',
        'weather:refresh-forecast-cache',
        'weather:sync-observations',
    ];

    public function path(): string
    {
        return storage_path('app/scheduler-failures.json');
    }

    public function tracks(string $command): bool
    {
        return in_array($this->commandName($command), self::TRACKED_COMMANDS, true);
    }

    public function record(string $command, int $exitCode, ?string $message = null): void
    {
        $entries = $this->all();

        array_unshift($entries, [
            'command' => $this->commandName($command),
            'exit_code' => $exitCode,
            'message' => $this->summarize($message),
            'failed_at' => now()->toIso8601String(),
        ]);

        $cutoff = now()->subDays(14);
        $entries = array_values(array_filter(
            $entries,
            function (array $entry) use ($cutoff): bool {
                try {
                    return Carbon::parse((string) ($entry['failed_at'] ?? ''))->greaterThanOrEqualTo($cutoff);
                } catch (\Throwable) {
                    return false;
                }
            },
        ));

        $entries = array_slice($entries, 0, 30);

        File::ensureDirectoryExists(dirname($this->path()));
        File::put($this->path(), json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), true);
    }

    /**
     * @return list<array{command: string, exit_code: int, message: string, failed_at: string, failed_at_label: string}>
     */
    public function recent(int $limit = 8, ?Carbon $since = null): array
    {
        $entries = $this->all();

        if ($since !== null) {
            $entries = array_values(array_filter(
                $entries,
                function (array $entry) use ($since): bool {
                    try {
                        return Carbon::parse((string) ($entry['failed_at'] ?? ''))->greaterThanOrEqualTo($since);
                    } catch (\Throwable) {
                        return false;
                    }
                },
            ));
        }

        return array_map(
            function (array $entry): array {
                $failedAt = (string) ($entry['failed_at'] ?? '');

                try {
                    $label = Carbon::parse($failedAt)->format('Y-m-d H:i:s');
                } catch (\Throwable) {
                    $label = $failedAt;
                }

                return [
                    'command' => (string) ($entry['command'] ?? ''),
                    'exit_code' => (int) ($entry['exit_code'] ?? 1),
                    'message' => (string) ($entry['message'] ?? ''),
                    'failed_at' => $failedAt,
                    'failed_at_label' => $label,
                ];
            },
            array_slice($entries, 0, $limit),
        );
    }

    public function countSince(Carbon $since): int
    {
        return count($this->recent(100, $since));
    }

    public function clear(): void
    {
        if (File::exists($this->path())) {
            File::delete($this->path());
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function all(): array
    {
        if (! File::exists($this->path())) {
            return [];
        }

        try {
            $decoded = json_decode((string) File::get($this->path()), true);
        } catch (\Throwable) {
            return [];
        }

        return is_array($decoded) ? array_values($decoded) : [];
    }

    private function commandName(string $command): string
    {
        $command = trim($command);

        if ($command === '') {
            return '';
        }

        $withoutBinary = preg_replace('/^(?:php(?:\s+\S+)?\s+)?artisan\s+/', '', $command) ?? $command;

        return explode(' ', trim((string) $withoutBinary), 2)[0];
    }

    private function summarize(?string $message): string
    {
        $message = trim((string) $message);

        if ($message === '') {
            return '';
        }

        $message = preg_replace('/\s+/', ' ', $message) ?? $message;

        return mb_substr($message, 0, 240);
    }
}
