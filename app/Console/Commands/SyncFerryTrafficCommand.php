<?php

namespace App\Console\Commands;

use App\Services\Trafikverket\FerryTrafficService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncFerryTrafficCommand extends Command
{
    protected $signature = 'ferry:sync-traffic {--diagnose : Testa API-anslutningen innan synk}';

    protected $description = 'Hämtar live färjetrafik från Trafikverket för Hemsöleden';

    public function handle(FerryTrafficService $ferryTraffic): int
    {
        if (! $ferryTraffic->isEnabled()) {
            $this->warn('TRAFIKVERKET_API_KEY saknas – live färjetrafik är avstängd.');
            $this->line('Lägg till nyckeln i .env och kör: php artisan config:clear');

            return self::FAILURE;
        }

        if ($this->option('diagnose')) {
            $this->printConfigSummary();
            $this->runVerboseChecks($ferryTraffic);
        }

        $count = $ferryTraffic->refreshTodayAndAhead();

        if ($count > 0) {
            $this->info("Uppdaterade färjetrafik för {$count} dag(ar).");

            return self::SUCCESS;
        }

        $this->warn('Ingen färjetrafik kunde hämtas från Trafikverket.');

        if ($error = $ferryTraffic->lastError()) {
            $this->error($error);
        } else {
            $this->error('Okänt fel – uppdaterade API-filer saknas troligen på servern.');
        }

        if (! $this->option('diagnose')) {
            $this->newLine();
            $this->line('Kör med flagga för mer info:');
            $this->line('  <comment>php artisan ferry:sync-traffic --diagnose</comment>');
        }

        return self::FAILURE;
    }

    private function printConfigSummary(): void
    {
        $apiKey = config('trafikverket.api_key');

        $this->line('Endpoint: '.config('trafikverket.endpoint'));
        $this->line('Färjeled: '.config('trafikverket.route_name'));
        $this->line('API-nyckel: '.(filled($apiKey) ? 'satt ('.Str::length((string) $apiKey).' tecken)' : 'saknas'));
        $this->newLine();
    }

    private function runVerboseChecks(FerryTrafficService $ferryTraffic): void
    {
        $this->info('Testar färjeled...');

        try {
            $routes = $ferryTraffic->probeRoutes();

            if ($routes === []) {
                $this->warn('  Ingen färjeled hittades.');
            } else {
                foreach ($routes as $route) {
                    $name = $route['Name'] ?? $route['name'] ?? '?';
                    $this->line('  · '.$name);
                }
            }
        } catch (\Throwable $exception) {
            $this->error('  '.$exception->getMessage());
        }

        $this->newLine();
        $this->info('Testar dagens avgångar...');

        try {
            $announcements = $ferryTraffic->probeAnnouncements(now()->startOfDay());
            $this->line('  Antal FerryAnnouncement: '.count($announcements));
        } catch (\Throwable $exception) {
            $this->error('  '.$exception->getMessage());
        }

        $this->newLine();
    }
}
