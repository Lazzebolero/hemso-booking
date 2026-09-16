<?php

namespace App\Listeners;

use App\Services\SchedulerFailureStore;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Log;

class RecordScheduledCommandFailure
{
    public function __construct(
        private SchedulerFailureStore $failures,
    ) {}

    public function handle(CommandFinished $event): void
    {
        if ($event->exitCode === 0 || ! $this->failures->tracks($event->command)) {
            return;
        }

        $this->failures->record($event->command, $event->exitCode);

        Log::error('Schemalagt jobb misslyckades: '.$event->command.' (exit '.$event->exitCode.')');
    }
}
