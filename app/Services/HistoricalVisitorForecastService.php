<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class HistoricalVisitorForecastService
{
    public function __construct(
        private HistoricalVisitorComparisonService $comparisonService,
    ) {}

    /**
     * @param  list<int>  $baselineYears
     */
    public function forecastForDate(Carbon $date, array $baselineYears): ?float
    {
        if ($baselineYears === []) {
            return null;
        }

        $values = [];

        foreach ($baselineYears as $year) {
            $count = $this->comparisonService->historicalParticipantsForYear($date, $year);

            if ($count !== null) {
                $values[] = $count;
            }
        }

        if ($values === []) {
            return null;
        }

        return array_sum($values) / count($values);
    }
}
