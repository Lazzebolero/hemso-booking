<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class WeatherObservationFormatter
{
    /**
     * @return array{label: string, abbreviation: string}
     */
    public static function windDirection(float $degrees): array
    {
        $normalized = fmod($degrees, 360);
        if ($normalized < 0) {
            $normalized += 360;
        }

        $index = (int) round($normalized / 45) % 8;

        $labels = [
            'nordlig',
            'nordostlig',
            'ostlig',
            'sydostlig',
            'sydlig',
            'sydvästlig',
            'västlig',
            'nordvästlig',
        ];

        $abbreviations = ['N', 'NO', 'O', 'SO', 'S', 'SV', 'V', 'NV'];

        return [
            'label' => $labels[$index],
            'abbreviation' => $abbreviations[$index],
        ];
    }

    public static function formatDecimal(float $value, int $decimals = 1): string
    {
        return rtrim(rtrim(number_format($value, $decimals, ',', ''), '0'), ',');
    }

    public static function formatVisibility(float $meters): string
    {
        if ($meters >= 1000) {
            return self::formatDecimal($meters / 1000, 0).' km';
        }

        return self::formatDecimal($meters, 0).' m';
    }

    public static function observationTimeLabel(int $timestampMs, string $timezone): string
    {
        $time = Carbon::createFromTimestampMs($timestampMs, $timezone);
        $now = now()->timezone($timezone);

        if ($time->isSameDay($now)) {
            return 'idag kl '.$time->format('H:i');
        }

        if ($time->isSameDay($now->copy()->subDay())) {
            return 'igår kl '.$time->format('H:i');
        }

        return $time->format('j/n H:i');
    }

    public static function hourlyPeriodLabel(int $endTimestampMs, string $timezone): string
    {
        $end = Carbon::createFromTimestampMs($endTimestampMs, $timezone);
        $start = $end->copy()->subHour();
        $prefix = $end->isSameDay(now()->timezone($timezone)) ? 'idag' : $end->format('j/n');

        return $prefix.' kl '.$start->format('H:i').' - '.$end->format('H:i');
    }

    public static function dailyPrecipitationPeriodLabel(string $timezone): string
    {
        $now = now()->timezone($timezone);
        $todayAtEight = $now->copy()->startOfDay()->setTime(8, 0);
        $yesterdayAtEight = $todayAtEight->copy()->subDay();

        return 'igår kl '.$yesterdayAtEight->format('H:i').' - idag kl '.$todayAtEight->format('H:i');
    }
}
