<?php

namespace App\Support;

use App\Models\WeatherDailyObservation;

class WeatherSummaryFormatter
{
    /**
     * @return array{temp: string|null, wind: string|null, precipitation: string|null}
     */
    public static function parts(?WeatherDailyObservation $observation): array
    {
        if ($observation === null) {
            return [
                'temp' => null,
                'wind' => null,
                'precipitation' => null,
            ];
        }

        $temp = null;
        $wind = null;
        $precipitation = null;

        if ($observation->temp_min !== null && $observation->temp_max !== null) {
            $temp = sprintf(
                '%s/%s°',
                self::formatNumber($observation->temp_min),
                self::formatNumber($observation->temp_max),
            );
        }

        if ($observation->wind_gust_max !== null) {
            $wind = self::formatNumber($observation->wind_gust_max).' m/s';
        } elseif ($observation->wind_speed_max !== null) {
            $wind = self::formatNumber($observation->wind_speed_max).' m/s';
        }

        if ($observation->precipitation_mm !== null) {
            $precipitation = self::formatNumber($observation->precipitation_mm).' mm';
        }

        return [
            'temp' => $temp,
            'wind' => $wind,
            'precipitation' => $precipitation,
        ];
    }

    public static function format(?WeatherDailyObservation $observation): ?string
    {
        $parts = array_values(array_filter(self::parts($observation)));

        return $parts === [] ? null : implode(', ', $parts);
    }

    /**
     * Compact day outlook for sidebar: symbol, max temp, rain when relevant.
     */
    public static function dayGlance(?string $symbolLabel, ?float $tempMax, ?float $precipitationMm): ?string
    {
        $parts = [];

        if (is_string($symbolLabel) && $symbolLabel !== '') {
            $parts[] = $symbolLabel;
        }

        if ($tempMax !== null) {
            $parts[] = 'upp till '.(string) (int) round($tempMax).'°';
        }

        if ($precipitationMm !== null && $precipitationMm >= 0.5) {
            $parts[] = $precipitationMm < 2.0
                ? 'lätt regn'
                : self::formatNumber($precipitationMm).' mm';
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }

    private static function formatNumber(float|string $value): string
    {
        $formatted = number_format((float) $value, 1, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted === '-0' ? '0' : $formatted;
    }
}
