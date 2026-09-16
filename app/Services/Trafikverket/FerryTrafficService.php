<?php

namespace App\Services\Trafikverket;

use App\Support\FerryDayTypes;
use App\Support\FerryDirections;
use App\Support\FerryStrinningenTimetable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FerryTrafficService
{
    private ?string $lastError = null;

    public function __construct(
        private TrafikverketApiClient $client,
    ) {}

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function isEnabled(): bool
    {
        return $this->client->isConfigured();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function cachedDay(CarbonInterface $date): ?array
    {
        return $this->ensureCached($date);
    }

    /**
     * @return list<array{level: string, title: string, message: string}>
     */
    public function alertsForDate(CarbonInterface $date, ?string $direction = null): array
    {
        $cached = $this->cachedDay($date);

        if ($cached === null) {
            return [];
        }

        $alerts = collect($cached['alerts'] ?? [])
            ->filter(fn (array $alert) => $this->alertMatchesDirection($alert, $direction))
            ->values()
            ->all();

        return $alerts;
    }

    /**
     * @return array<string, array{
     *     scheduled_time: string,
     *     departure_time: ?string,
     *     is_cancelled: bool,
     *     delay_minutes: ?int,
     *     message: ?string,
     *     source: string
     * }>
     */
    public function statusMapForDate(CarbonInterface $date, string $direction): array
    {
        $cached = $this->cachedDay($date);

        if ($cached === null) {
            return [];
        }

        $map = [];
        $harbor = $this->fromHarborForDirection($direction);

        foreach ($cached['announcements'] ?? [] as $announcement) {
            if (! is_array($announcement)) {
                continue;
            }

            if (! $this->announcementMatchesFromHarbor($announcement, $harbor)) {
                continue;
            }

            $scheduled = $announcement['scheduled_time']
                ?? $this->announcementScheduledTime($announcement, $date);

            if ($scheduled === null) {
                continue;
            }

            $map[$scheduled] = [
                'scheduled_time' => $scheduled,
                'departure_time' => $announcement['departure_time'] ?? null,
                'is_cancelled' => (bool) ($announcement['is_cancelled'] ?? false),
                'delay_minutes' => $announcement['delay_minutes'] ?? null,
                'message' => $announcement['message'] ?? null,
                'source' => $announcement['source'] ?? 'trafikverket',
            ];
        }

        return $map;
    }

    /**
     * Senaste faktiska avgång från Strinningen enligt Trafikverket (inkl. extraturer).
     *
     * @return array{
     *     time: string,
     *     departed_at: string,
     *     is_extra: bool,
     *     minutes_ago: int,
     *     guest_arrival_minutes_min: int,
     *     guest_arrival_minutes_max: int
     * }|null
     */
    public function lastLiveDeparture(CarbonInterface $date, string $direction, ?CarbonInterface $at = null): ?array
    {
        if ($direction !== FerryDirections::TO_ISLAND || ! $this->isEnabled()) {
            return null;
        }

        $cached = $this->cachedDay($date);

        if ($cached === null) {
            return null;
        }

        $at ??= now();
        $harbor = (string) config('trafikverket.harbors.strinningen', 'Strinningen');
        $guestMin = (int) config('trafikverket.guest_travel_minutes_min', 15);
        $guestMax = (int) config('trafikverket.guest_travel_minutes_max', 20);
        $latestAt = null;
        $latest = null;

        foreach ($cached['announcements'] ?? [] as $announcement) {
            if (! is_array($announcement)) {
                continue;
            }

            if (($announcement['is_cancelled'] ?? false) === true) {
                continue;
            }

            $fromHarbor = (string) ($announcement['from_harbor'] ?? '');

            if ($fromHarbor === '' || Str::lower($fromHarbor) !== Str::lower($harbor)) {
                continue;
            }

            $departedAt = $this->parseApiDateTime($announcement['departure_at'] ?? null);

            if ($departedAt === null || $departedAt->gt($at)) {
                continue;
            }

            if ($latestAt !== null && ! $departedAt->gt($latestAt)) {
                continue;
            }

            $latestAt = $departedAt;
            $latest = $announcement;
        }

        if ($latestAt === null || $latest === null) {
            return null;
        }

        return [
            'time' => $latestAt->format('H:i'),
            'departed_at' => $latestAt->toIso8601String(),
            'is_extra' => (bool) ($latest['is_extra'] ?? false),
            'minutes_ago' => (int) $latestAt->diffInMinutes($at),
            'guest_arrival_minutes_min' => $guestMin,
            'guest_arrival_minutes_max' => $guestMax,
        ];
    }

    public function refreshForDate(CarbonInterface $date): bool
    {
        if (! $this->isEnabled()) {
            $this->lastError = 'TRAFIKVERKET_API_KEY saknas.';

            return false;
        }

        $this->lastError = null;

        try {
            $announcements = $this->client->extractObjects(
                $this->client->postQuery($this->buildAnnouncementsRequestXml($date)),
                'FerryAnnouncement',
            );

            $situations = $this->fetchSituations($date);

            $normalizedAnnouncements = collect($announcements)
                ->filter(fn (array $item) => $this->announcementMatchesRoute($item))
                ->map(fn (array $item) => $this->normalizeAnnouncement($item, $date))
                ->filter()
                ->values()
                ->all();

            $alerts = $this->buildAlerts($normalizedAnnouncements, $situations, $date);

            Cache::put($this->cacheKey($date), [
                'fetched_at' => now()->toIso8601String(),
                'announcements' => $normalizedAnnouncements,
                'alerts' => $alerts,
            ], now()->addSeconds((int) config('trafikverket.cache_ttl_seconds', 180)));

            return true;
        } catch (\Throwable $exception) {
            $this->lastError = $exception->getMessage();
            report($exception);

            return false;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function probeAnnouncements(CarbonInterface $date): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        $payload = $this->client->postQuery($this->buildAnnouncementsRequestXml($date));

        return $this->client->extractObjects($payload, 'FerryAnnouncement');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function probeRoutes(): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        $routeName = e((string) config('trafikverket.route_name'));
        $apiKey = e((string) config('trafikverket.api_key'));

        $xml = <<<XML
<REQUEST>
  <LOGIN authenticationkey="{$apiKey}" />
  <QUERY objecttype="FerryRoute" schemaversion="1.2" limit="10">
    <FILTER>
      <LIKE name="Name" value="%{$routeName}%" />
    </FILTER>
  </QUERY>
</REQUEST>
XML;

        $payload = $this->client->postQuery($xml);

        return $this->client->extractObjects($payload, 'FerryRoute');
    }

    public function refreshTodayAndAhead(): int
    {
        $days = max(0, (int) config('trafikverket.sync_days_ahead', 1));
        $success = 0;

        for ($offset = 0; $offset <= $days; $offset++) {
            if ($this->refreshForDate(now()->startOfDay()->addDays($offset))) {
                $success++;
            }
        }

        return $success;
    }

    private function cacheKey(CarbonInterface $date): string
    {
        return 'ferry_traffic:'.$date->toDateString();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function ensureCached(CarbonInterface $date): ?array
    {
        $cached = Cache::get($this->cacheKey($date));

        if (is_array($cached)) {
            return $cached;
        }

        return null;
    }

    private function buildAnnouncementsRequestXml(CarbonInterface $date): string
    {
        $apiKey = e((string) config('trafikverket.api_key'));
        $routeName = e((string) config('trafikverket.route_name'));
        $start = $date->copy()->startOfDay()->format('Y-m-d\TH:i:s');
        $end = $date->copy()->addDay()->startOfDay()->format('Y-m-d\TH:i:s');

        return <<<XML
<REQUEST>
  <LOGIN authenticationkey="{$apiKey}" />
  <QUERY objecttype="FerryAnnouncement" schemaversion="1.2" orderby="DepartureTime asc" limit="300">
    <FILTER>
      <AND>
        <EQ name="Route.Name" value="{$routeName}" />
        <GTE name="DepartureTime" value="{$start}" />
        <LT name="DepartureTime" value="{$end}" />
      </AND>
    </FILTER>
  </QUERY>
</REQUEST>
XML;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchSituations(CarbonInterface $date): array
    {
        try {
            $payload = $this->client->postQuery($this->buildSituationsRequestXml($date));

            return $this->client->extractObjects($payload, 'Situation');
        } catch (\Throwable $exception) {
            if ($this->isUnsupportedObjectTypeException($exception)) {
                // Trafikverket har tagit bort/ändrat Situation i vissa miljöer.
                // Färjeavvikelser hämtas redan via FerryAnnouncement.
                return [];
            }

            report($exception);

            return [];
        }
    }

    private function buildSituationsRequestXml(CarbonInterface $date): string
    {
        $apiKey = e((string) config('trafikverket.api_key'));
        $situationStart = $date->copy()->subDay()->startOfDay()->format('Y-m-d\TH:i:s');

        return <<<XML
<REQUEST>
  <LOGIN authenticationkey="{$apiKey}" />
  <QUERY objecttype="Situation" schemaversion="1.6" orderby="PublicationTime desc" limit="30">
    <FILTER>
      <AND>
        <GTE name="PublicationTime" value="{$situationStart}" />
        <EQ name="Deleted" value="false" />
      </AND>
    </FILTER>
  </QUERY>
</REQUEST>
XML;
    }

    private function isUnsupportedObjectTypeException(\Throwable $exception): bool
    {
        $message = Str::lower($exception->getMessage());

        return Str::contains($message, 'objecttype')
            && (Str::contains($message, 'does not exist') || Str::contains($message, 'does not exists'));
    }

    /**
     * @param  array<string, mixed>  $announcement
     */
    private function announcementMatchesRoute(array $announcement): bool
    {
        $route = (string) (
            $announcement['RouteName']
            ?? $announcement['FerryRouteName']
            ?? data_get($announcement, 'Route.Name')
            ?? ''
        );

        return Str::lower($route) === Str::lower((string) config('trafikverket.route_name'));
    }

    /**
     * @param  array<string, mixed>  $announcement
     */
    private function announcementMatchesFromHarbor(array $announcement, string $harbor): bool
    {
        $fromHarbor = $announcement['from_harbor']
            ?? $this->harborName($announcement['FromHarbor'] ?? $announcement['FromHarbour'] ?? null);

        return $fromHarbor !== null && Str::lower($fromHarbor) === Str::lower($harbor);
    }

    /**
     * @param  array<string, mixed>  $announcement
     */
    private function announcementMatchesHarbor(array $announcement, string $harbor): bool
    {
        return $this->announcementMatchesFromHarbor($announcement, $harbor);
    }

    /**
     * @param  array<string, mixed>  $announcement
     */
    private function normalizeAnnouncement(array $announcement, CarbonInterface $date): ?array
    {
        $scheduled = $this->announcementScheduledTime($announcement, $date);
        $fromHarbor = $this->harborName($announcement['FromHarbor'] ?? $announcement['FromHarbour'] ?? null);
        $toHarbor = $this->harborName($announcement['ToHarbor'] ?? $announcement['ToHarbour'] ?? null);

        if ($scheduled === null || $fromHarbor === null) {
            return null;
        }

        $status = $this->normalizeAnnouncementStatus($announcement, $scheduled);

        $dayType = FerryDayTypes::forDate($date);
        $timetableTimes = collect(FerryStrinningenTimetable::departuresFor($dayType))
            ->pluck('time')
            ->all();

        return array_merge($status, [
            'from_harbor' => $fromHarbor,
            'to_harbor' => $toHarbor,
            'is_extra' => ! in_array($scheduled, $timetableTimes, true),
        ]);
    }

    /**
     * @param  array<string, mixed>  $announcement
     * @return array{
     *     scheduled_time: string,
     *     departure_time: ?string,
     *     is_cancelled: bool,
     *     delay_minutes: ?int,
     *     message: ?string,
     *     source: string
     * }
     */
    private function normalizeAnnouncementStatus(array $announcement, string $scheduled): array
    {
        $departureAt = $this->parseApiDateTime($announcement['DepartureTime'] ?? null);
        $scheduledAt = $this->parseApiDateTime($announcement['TimeTabledDepartureTime'] ?? null)
            ?? Carbon::parse($scheduled);

        $isCancelled = $this->toBool($announcement['DepartureIsCancelled'] ?? $announcement['Canceled'] ?? false);
        $delayMinutes = null;

        if ($departureAt !== null && $scheduledAt !== null && ! $isCancelled) {
            $delayMinutes = (int) $scheduledAt->diffInMinutes($departureAt, false);

            if ($delayMinutes <= 1) {
                $delayMinutes = null;
            }
        }

        $message = $this->firstNonEmptyString([
            data_get($announcement, 'Deviation.Text'),
            data_get($announcement, 'Deviation.Message'),
            data_get($announcement, 'DepartureAdvice'),
            is_array($announcement['OtherInformation'] ?? null)
                ? implode(' ', array_filter($announcement['OtherInformation']))
                : ($announcement['OtherInformation'] ?? null),
        ]);

        return [
            'scheduled_time' => $scheduled,
            'departure_time' => $departureAt?->format('H:i'),
            'departure_at' => $departureAt?->toIso8601String(),
            'is_cancelled' => $isCancelled,
            'delay_minutes' => $delayMinutes,
            'message' => $message,
            'source' => 'trafikverket',
        ];
    }

    /**
     * @param  array<string, mixed>  $announcement
     */
    private function announcementScheduledTime(array $announcement, CarbonInterface $date): ?string
    {
        $scheduledAt = $this->parseApiDateTime($announcement['TimeTabledDepartureTime'] ?? null)
            ?? $this->parseApiDateTime($announcement['DepartureTime'] ?? null);

        if ($scheduledAt === null || $scheduledAt->toDateString() !== $date->toDateString()) {
            return null;
        }

        return $scheduledAt->format('H:i');
    }

    /**
     * @param  list<array<string, mixed>>  $announcements
     * @param  list<array<string, mixed>>  $situations
     * @return list<array{level: string, title: string, message: string, direction: ?string}>
     */
    private function buildAlerts(array $announcements, array $situations, CarbonInterface $date): array
    {
        $alerts = [];
        $now = now();

        foreach ($announcements as $announcement) {
            if (! ($announcement['is_cancelled'] ?? false)) {
                continue;
            }

            $scheduledAt = Carbon::parse($date->toDateString().' '.($announcement['scheduled_time'] ?? '00:00').':00');

            if ($scheduledAt->lt($now->copy()->subHours(2))) {
                continue;
            }

            $alerts[] = [
                'level' => 'danger',
                'title' => 'Inställd avgång '.$announcement['scheduled_time'],
                'message' => trim(($announcement['message'] ?? '') ?: 'Avgången från '.($announcement['from_harbor'] ?? 'hamnen').' är inställd enligt Trafikverket.'),
                'direction' => $this->directionForHarbor((string) ($announcement['from_harbor'] ?? '')),
            ];
        }

        foreach ($announcements as $announcement) {
            $delay = $announcement['delay_minutes'] ?? null;

            if ($delay === null || ($announcement['is_cancelled'] ?? false)) {
                continue;
            }

            $scheduledAt = Carbon::parse($date->toDateString().' '.($announcement['scheduled_time'] ?? '00:00').':00');

            if ($scheduledAt->lt($now->copy()->subMinutes(30))) {
                continue;
            }

            $alerts[] = [
                'level' => 'warning',
                'title' => 'Försening '.$announcement['scheduled_time'],
                'message' => trim(($announcement['message'] ?? '') ?: 'Ny avgång '.($announcement['departure_time'] ?? '-').' i stället för planerad '.$announcement['scheduled_time'].'.'),
                'direction' => $this->directionForHarbor((string) ($announcement['from_harbor'] ?? '')),
            ];
        }

        $extraAlertMinutes = (int) config('trafikverket.extra_departure_alert_minutes', 25);
        $guestMin = (int) config('trafikverket.guest_travel_minutes_min', 15);
        $guestMax = (int) config('trafikverket.guest_travel_minutes_max', 20);

        foreach ($announcements as $announcement) {
            if (! ($announcement['is_extra'] ?? false) || ($announcement['is_cancelled'] ?? false)) {
                continue;
            }

            $departedAt = $this->parseApiDateTime($announcement['departure_at'] ?? null);

            if ($departedAt === null || $departedAt->gt($now) || $departedAt->lt($now->copy()->subMinutes($extraAlertMinutes))) {
                continue;
            }

            $alerts[] = [
                'level' => 'info',
                'title' => 'Extratur avgick '.$departedAt->format('H:i'),
                'message' => 'Färjan var troligen full. Räkna med gäster från färjeläget om cirka '.$guestMin.'–'.$guestMax.' minuter.',
                'direction' => $this->directionForHarbor((string) ($announcement['from_harbor'] ?? '')),
            ];
        }

        foreach ($situations as $situation) {
            if (! is_array($situation)) {
                continue;
            }

            if (! $this->situationIsRelevant($situation)) {
                continue;
            }

            $message = $this->firstNonEmptyString([
                data_get($situation, 'Message.Value'),
                data_get($situation, 'Message'),
                data_get($situation, 'Header'),
            ]);

            if ($message === null) {
                continue;
            }

            $alerts[] = [
                'level' => 'warning',
                'title' => $this->firstNonEmptyString([
                    data_get($situation, 'Header'),
                ]) ?? 'Trafikinformation Hemsöleden',
                'message' => $message,
                'direction' => null,
            ];
        }

        return collect($alerts)
            ->unique(fn (array $alert) => $alert['title'].'|'.$alert['message'])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $situation
     */
    private function situationIsRelevant(array $situation): bool
    {
        $haystack = Str::lower(json_encode($situation, JSON_UNESCAPED_UNICODE) ?: '');

        foreach (['hemsöleden', 'hemsö', 'strinningen'] as $needle) {
            if (Str::contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{direction: ?string}  $alert
     */
    private function alertMatchesDirection(array $alert, ?string $direction): bool
    {
        if ($direction === null || empty($alert['direction'])) {
            return true;
        }

        return $alert['direction'] === $direction;
    }

    private function fromHarborForDirection(string $direction): string
    {
        $harbors = config('trafikverket.harbors');

        return $direction === FerryDirections::TO_MAINLAND
            ? (string) ($harbors['hemso'] ?? 'Hemsö')
            : (string) ($harbors['strinningen'] ?? 'Strinningen');
    }

    private function directionForHarbor(string $harbor): ?string
    {
        $harbors = config('trafikverket.harbors');

        if (Str::lower($harbor) === Str::lower((string) ($harbors['strinningen'] ?? 'Strinningen'))) {
            return FerryDirections::TO_ISLAND;
        }

        if (Str::lower($harbor) === Str::lower((string) ($harbors['hemso'] ?? 'Hemsö'))) {
            return FerryDirections::TO_MAINLAND;
        }

        return null;
    }

    private function harborName(mixed $harbor): ?string
    {
        if (is_string($harbor) && $harbor !== '') {
            return $harbor;
        }

        if (! is_array($harbor)) {
            return null;
        }

        return $this->firstNonEmptyString([
            $harbor['Name'] ?? null,
            $harbor['name'] ?? null,
        ]);
    }

    private function parseApiDateTime(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes'], true);
        }

        return (bool) $value;
    }

    /**
     * @param  list<mixed>  $values
     */
    private function firstNonEmptyString(array $values): ?string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
