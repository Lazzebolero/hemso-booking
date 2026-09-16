<?php

namespace App\Services;

use App\Services\Smhi\SmhiWeatherWarningsClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class WeatherWarningsService
{
    /** @var list<string> */
    private const SEVERITY_ORDER = ['MESSAGE', 'YELLOW', 'ORANGE', 'RED'];

    public function __construct(
        public SmhiWeatherWarningsClient $warningsClient,
    ) {}

    /**
     * @return array{
     *     has_warnings: bool,
     *     highest_severity: string|null,
     *     highest_severity_label: string|null,
     *     warnings: list<array<string, mixed>>,
     *     fetched_at: string|null
     * }
     */
    public function presentation(): array
    {
        $cacheSeconds = (int) config('smhi_weather.warnings_cache_seconds', 300);

        return Cache::remember(
            'smhi.weather.warnings.presentation',
            $cacheSeconds,
            function (): array {
                try {
                    return $this->buildPresentation();
                } catch (\Throwable) {
                    return $this->emptyPresentation();
                }
            },
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function sidebarAlert(): ?array
    {
        try {
            $presentation = $this->presentation();

            if (! $presentation['has_warnings']) {
                return null;
            }

            $top = $presentation['warnings'][0] ?? null;

            if ($top === null) {
                return null;
            }

            return [
                'severity' => $top['severity'],
                'severity_label' => $top['severity_label'],
                'event_label' => $top['event_label'],
                'summary' => $top['summary'],
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{
     *     has_warnings: bool,
     *     highest_severity: string|null,
     *     highest_severity_label: string|null,
     *     warnings: list<array<string, mixed>>,
     *     fetched_at: string|null
     * }
     */
    /**
     * @return array{
     *     has_warnings: bool,
     *     highest_severity: string|null,
     *     highest_severity_label: string|null,
     *     warnings: list<array<string, mixed>>,
     *     fetched_at: string|null
     * }
     */
    private function emptyPresentation(): array
    {
        return [
            'has_warnings' => false,
            'highest_severity' => null,
            'highest_severity_label' => null,
            'warnings' => [],
            'fetched_at' => null,
        ];
    }

    private function buildPresentation(): array
    {
        $timezone = (string) config('smhi_weather.timezone', 'Europe/Stockholm');
        $warnings = $this->warningsClient->fetchActiveWarnings();
        $parsed = $this->parseWarnings($warnings, $timezone);

        usort($parsed, function (array $left, array $right): int {
            $severityCompare = $this->severityRank((string) $right['severity'])
                <=> $this->severityRank((string) $left['severity']);

            if ($severityCompare !== 0) {
                return $severityCompare;
            }

            $leftStart = $left['starts_at'] ?? '';
            $rightStart = $right['starts_at'] ?? '';

            return strcmp((string) $leftStart, (string) $rightStart);
        });

        $highestSeverity = $parsed[0]['severity'] ?? null;
        $highestSeverityLabel = $parsed[0]['severity_label'] ?? null;

        return [
            'has_warnings' => $parsed !== [],
            'highest_severity' => $highestSeverity,
            'highest_severity_label' => $highestSeverityLabel,
            'warnings' => $parsed,
            'fetched_at' => now()->timezone($timezone)->toIso8601String(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $warnings
     * @return list<array<string, mixed>>
     */
    private function parseWarnings(array $warnings, string $timezone): array
    {
        $areaIds = array_map('intval', (array) config('smhi_weather.warning_area_ids', []));
        $eventCodes = array_map('strtoupper', (array) config('smhi_weather.warning_event_codes', []));
        $parsed = [];

        foreach ($warnings as $warning) {
            $event = is_array($warning['event'] ?? null) ? $warning['event'] : [];
            $eventCode = strtoupper((string) ($event['code'] ?? ''));

            if ($eventCodes !== [] && ! in_array($eventCode, $eventCodes, true)) {
                continue;
            }

            $eventLabel = (string) ($event['sv'] ?? $event['en'] ?? 'Vädervarning');
            $warningAreas = is_array($warning['warningAreas'] ?? null) ? $warning['warningAreas'] : [];

            foreach ($warningAreas as $warningArea) {
                if (! is_array($warningArea)) {
                    continue;
                }

                if (! $this->matchesArea($warningArea, $areaIds)) {
                    continue;
                }

                $severity = strtoupper((string) ($warningArea['warningLevel']['code'] ?? 'MESSAGE'));
                $incidentText = $this->descriptionText($warningArea, 'INCIDENT');
                $startsAt = $this->formatTimestamp($warningArea['approximateStart'] ?? null, $timezone);
                $endsAt = $this->formatTimestamp($warningArea['approximateEnd'] ?? null, $timezone);
                $areaName = (string) ($warningArea['areaName']['sv'] ?? $warningArea['areaName']['en'] ?? '');

                $parsed[] = [
                    'event_code' => $eventCode,
                    'event_label' => $eventLabel,
                    'severity' => $severity,
                    'severity_label' => (string) ($warningArea['warningLevel']['sv'] ?? $severity),
                    'area_name' => $areaName,
                    'summary' => $this->buildSummary($eventLabel, $severity, $areaName, $eventCode),
                    'incident' => $incidentText,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'period_label' => $this->buildPeriodLabel($startsAt, $endsAt),
                ];
            }
        }

        return $parsed;
    }

    /**
     * @param  array<string, mixed>  $warningArea
     * @param  list<int>  $areaIds
     */
    private function matchesArea(array $warningArea, array $areaIds): bool
    {
        if ($areaIds === []) {
            return true;
        }

        $affectedAreas = is_array($warningArea['affectedAreas'] ?? null) ? $warningArea['affectedAreas'] : [];

        foreach ($affectedAreas as $affectedArea) {
            if (! is_array($affectedArea)) {
                continue;
            }

            $id = (int) ($affectedArea['id'] ?? 0);

            if (in_array($id, $areaIds, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $warningArea
     */
    private function descriptionText(array $warningArea, string $code): ?string
    {
        $descriptions = is_array($warningArea['descriptions'] ?? null) ? $warningArea['descriptions'] : [];

        foreach ($descriptions as $description) {
            if (! is_array($description)) {
                continue;
            }

            $titleCode = (string) ($description['title']['code'] ?? '');

            if ($titleCode !== $code) {
                continue;
            }

            $text = (string) ($description['text']['sv'] ?? $description['text']['en'] ?? '');

            return $text !== '' ? $text : null;
        }

        return null;
    }

    private function buildSummary(string $eventLabel, string $severity, string $areaName, string $eventCode = ''): string
    {
        if ($eventCode === 'FIRE') {
            if ($areaName !== '') {
                return "Brandrisk ({$areaName})";
            }

            return 'Brandrisk';
        }

        if ($eventCode === 'HIGH_TEMPERATURES') {
            if ($areaName !== '') {
                return "Höga temperaturer ({$areaName})";
            }

            return 'Höga temperaturer';
        }

        $severityLabel = match ($severity) {
            'RED' => 'Röd varning',
            'ORANGE' => 'Orange varning',
            'YELLOW' => 'Gul varning',
            default => 'Meddelande',
        };

        if ($areaName !== '') {
            return "{$severityLabel}: {$eventLabel} ({$areaName})";
        }

        return "{$severityLabel}: {$eventLabel}";
    }

    private function buildPeriodLabel(?string $startsAt, ?string $endsAt): ?string
    {
        if ($startsAt === null && $endsAt === null) {
            return null;
        }

        if ($startsAt !== null && $endsAt !== null) {
            return "{$startsAt} – {$endsAt}";
        }

        if ($startsAt !== null) {
            return "Från {$startsAt}";
        }

        return "Till {$endsAt}";
    }

    private function formatTimestamp(mixed $value, string $timezone): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return Carbon::parse($value)
            ->timezone($timezone)
            ->format('j/n H:i');
    }

    private function severityRank(string $severity): int
    {
        $index = array_search($severity, self::SEVERITY_ORDER, true);

        return $index === false ? -1 : $index;
    }
}
