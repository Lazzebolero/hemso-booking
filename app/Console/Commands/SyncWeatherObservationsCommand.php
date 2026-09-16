<?php

namespace App\Console\Commands;

use App\Services\Smhi\SmhiMetObsClient;
use App\Services\WeatherObservationSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncWeatherObservationsCommand extends Command
{
    protected $signature = 'weather:sync-observations
                            {--from= : Startdatum (YYYY-MM-DD)}
                            {--to= : Slutdatum (YYYY-MM-DD)}
                            {--recent : Hämta senaste månaderna i stället för arkiv}';

    protected $description = 'Hämtar dagliga väderobservationer från SMHI Lungö A (standard: t.o.m. gårdagen)';

    public function handle(
        WeatherObservationSyncService $syncService,
        SmhiMetObsClient $client,
    ): int {
        $from = $this->resolveFromDate();
        $to = $this->resolveToDate();
        $useArchive = ! $this->option('recent');

        $this->line('Station: '.config('smhi_weather.station_name').' ('.config('smhi_weather.station_id').')');
        $this->line("Period: {$from->toDateString()} – {$to->toDateString()}");
        $this->line('Källa: '.($useArchive ? 'corrected-archive' : 'latest-months'));
        $this->newLine();

        try {
            $saved = $syncService->syncDateRange($from, $to, $useArchive);
        } catch (\Throwable $exception) {
            $this->error('Synk misslyckades: '.$exception->getMessage());

            if (! $client->probeConnection()) {
                $this->warn('Kunde inte nå SMHI MetObs API.');
            }

            return self::FAILURE;
        }

        if ($saved > 0) {
            $this->info("Sparade {$saved} dag(ar) med väderdata.");

            return self::SUCCESS;
        }

        $this->error('Ingen väderdata sparades för vald period.');

        return self::FAILURE;
    }

    private function resolveFromDate(): Carbon
    {
        $from = $this->option('from');

        if (is_string($from) && $from !== '') {
            return Carbon::parse($from)->startOfDay();
        }

        if ($this->option('recent')) {
            return now()->subDays(7)->startOfDay();
        }

        return Carbon::parse((string) config('smhi_weather.default_backfill_from'))->startOfDay();
    }

    private function resolveToDate(): Carbon
    {
        $to = $this->option('to');

        if (is_string($to) && $to !== '') {
            return Carbon::parse($to)->startOfDay();
        }

        return now()->subDay()->startOfDay();
    }
}
