<?php

namespace App\Console\Commands;

use App\Services\WeatherForecastService;
use App\Services\WeatherWarningsService;
use Illuminate\Console\Command;

class RefreshWeatherForecastCacheCommand extends Command
{
    protected $signature = 'weather:refresh-forecast-cache';

    protected $description = 'Förladdar cachen för väderwidget och prognossida från SMHI';

    public function handle(
        WeatherForecastService $forecastService,
        WeatherWarningsService $warningsService,
    ): int {
        try {
            $forecastService->presentation();
            $warningsService->presentation();

            $this->info('Vädercache uppdaterad.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Vädercache kunde inte uppdateras: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
