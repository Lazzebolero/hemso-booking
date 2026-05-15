<?php

namespace App\Console\Commands;

use App\Support\DeploySmokeRunner;
use Illuminate\Console\Command;

class DeploySmokeCommand extends Command
{
    protected $signature = 'deploy:smoke
                            {--url= : Bas-URL för HTTP-röktest (t.ex. https://staging.example.com)}
                            {--strict : Behandla varningar som fel}';

    protected $description = 'Kör deploy-röktest (lokala kontroller och valfritt HTTP mot staging/prod)';

    public function handle(DeploySmokeRunner $runner): int
    {
        $baseUrl = $this->option('url');
        $checks = $runner->run(is_string($baseUrl) ? $baseUrl : null);

        $rows = array_map(
            fn (array $check): array => [
                $check['name'],
                strtoupper($check['status']),
                $check['message'],
            ],
            $checks
        );

        $this->table(['Kontroll', 'Status', 'Meddelande'], $rows);

        $hasError = collect($checks)->contains(fn (array $check): bool => $check['status'] === 'error');
        $hasWarning = collect($checks)->contains(fn (array $check): bool => $check['status'] === 'warning');

        if ($hasError || ($this->option('strict') && $hasWarning)) {
            $this->error('Röktestet misslyckades.');

            if ($this->option('strict') && $hasWarning) {
                $warningNames = collect($checks)
                    ->where('status', 'warning')
                    ->pluck('name')
                    ->implode(', ');

                $this->line("Varningar (--strict): {$warningNames}");
            }

            if (app()->environment('local') && ! $this->option('url')) {
                $this->line('Tips: På servern/staging, kör med --url=https://din-domän.se --strict efter deploy.');
            }

            return self::FAILURE;
        }

        if ($hasWarning) {
            $this->warn('Röktestet passerade med varningar.');
        } else {
            $this->info('Röktestet passerade.');
        }

        return self::SUCCESS;
    }
}
