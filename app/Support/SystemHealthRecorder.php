<?php

namespace App\Support;

use App\Models\SystemHealthSnapshot;
use Illuminate\Support\Collection;

class SystemHealthRecorder
{
    public function __construct(private SystemHealthChecker $checker) {}

    /**
     * @param  array<string, array{key: string, status: string, title: string, message: string, items: array<string, string>, groups: list<array{title: string, items: array<string, string>}>}>  $checks
     */
    public function record(array $checks, string $overallStatus): SystemHealthSnapshot
    {
        $snapshot = SystemHealthSnapshot::query()->create([
            'overall_status' => $overallStatus,
            'checks' => $this->compactChecksForStorage($checks),
            'checked_at' => now(),
        ]);

        SystemHealthSnapshot::query()
            ->where('checked_at', '<', now()->subDays(30))
            ->delete();

        return $snapshot;
    }

    /**
     * @return Collection<int, SystemHealthSnapshot>
     */
    public function recent(int $limit = 15): Collection
    {
        return SystemHealthSnapshot::query()
            ->orderByDesc('checked_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<string, array{key: string, status: string, title: string, message: string, items: array<string, string>, groups: list<array{title: string, items: array<string, string>}>}>  $checks
     * @return list<array{key: string, status: string, title: string, message: string}>
     */
    private function compactChecksForStorage(array $checks): array
    {
        return array_values(array_map(
            fn (array $check): array => [
                'key' => $check['key'],
                'status' => $check['status'],
                'title' => $check['title'],
                'message' => $check['message'],
            ],
            $checks
        ));
    }
}
