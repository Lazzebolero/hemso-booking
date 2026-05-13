<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SchedulerHeartbeat extends Command
{
    protected $signature = 'scheduler:heartbeat';

    protected $description = 'Writes a heartbeat timestamp for scheduler monitoring';

    public function handle(): int
    {
        $path = storage_path('app/scheduler-heartbeat.json');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        file_put_contents($path, json_encode([
            'ran_at' => now()->toIso8601String(),
            'timestamp' => now()->timestamp,
        ], JSON_PRETTY_PRINT));

        $this->info('Scheduler heartbeat written to: ' . $path);

        return self::SUCCESS;
    }
}