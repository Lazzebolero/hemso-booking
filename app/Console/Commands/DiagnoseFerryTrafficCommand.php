<?php

namespace App\Console\Commands;

use App\Services\Trafikverket\FerryTrafficService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DiagnoseFerryTrafficCommand extends Command
{
    protected $signature = 'ferry:diagnose-traffic';

    protected $description = 'Testar anslutningen till Trafikverkets API för Hemsöleden';

    public function handle(FerryTrafficService $ferryTraffic): int
    {
        $apiKey = config('trafikverket.api_key');
        $endpoint = config('trafikverket.endpoint');
        $routeName = config('trafikverket.route_name');

        $this->line('Endpoint: '.$endpoint);
        $this->line('Färjeled: '.$routeName);
        $this->line('API-nyckel: '.(filled($apiKey) ? 'satt ('.Str::length((string) $apiKey).' tecken)' : 'saknas'));

        if (! $ferryTraffic->isEnabled()) {
            $this->error('TRAFIKVERKET_API_KEY saknas i .env – lägg till nyckeln och kör php artisan config:clear.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('1. Söker färjeled...');

        try {
            $routes = $ferryTraffic->probeRoutes();

            if ($routes === []) {
                $this->warn('Ingen färjeled hittades med namnet "'.$routeName.'".');
            } else {
                foreach ($routes as $route) {
                    $name = $route['Name'] ?? $route['name'] ?? '?';
                    $this->line('  · '.$name);
                }
            }
        } catch (\Throwable $exception) {
            $this->error('Färjeledssökning misslyckades: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('2. Hämtar dagens avgångar...');

        try {
            $announcements = $ferryTraffic->probeAnnouncements(now()->startOfDay());
            $this->line('  Antal FerryAnnouncement: '.count($announcements));

            foreach (array_slice($announcements, 0, 3) as $announcement) {
                $from = data_get($announcement, 'FromHarbor.Name')
                    ?? data_get($announcement, 'FromHarbor.name')
                    ?? '?';
                $time = $announcement['DepartureTime'] ?? '?';
                $route = data_get($announcement, 'Route.Name') ?? $announcement['RouteName'] ?? '?';

                $this->line("  · {$time} från {$from} ({$route})");
            }

            if (count($announcements) > 3) {
                $this->line('  · ...');
            }
        } catch (\Throwable $exception) {
            $this->error('Avgångshämtning misslyckades: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('3. Synkar cache...');

        if ($ferryTraffic->refreshForDate(now()->startOfDay())) {
            $this->info('Synk lyckades för idag.');
        } else {
            $this->error('Synk misslyckades: '.($ferryTraffic->lastError() ?? 'okänt fel'));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
