<?php

namespace App\Services;

use App\Models\Setting;

class EconomySettingsService
{
    public const KEY_GUIDE_HOURLY_COST = 'economy_guide_hourly_cost';

    public const KEY_RESTAURANT_HOURLY_COST = 'economy_restaurant_hourly_cost';

    public const KEY_OB_HOURLY_AMOUNT = 'economy_ob_hourly_amount';

    public const KEY_PRICE_ADULT = 'economy_price_adult';

    public const KEY_PRICE_YOUTH = 'economy_price_youth';

    public const KEY_PRICE_CHILD = 'economy_price_child';

    public const KEY_CHILD_UNDER4_PERCENT = 'economy_child_under4_percent';

    public const KEY_NOTIFICATION_EMAIL = 'economics_notification_email';

    /**
     * @return array{
     *     economy_guide_hourly_cost: float,
     *     economy_restaurant_hourly_cost: float,
     *     economy_ob_hourly_amount: float,
     *     economy_price_adult: float,
     *     economy_price_youth: float,
     *     economy_price_child: float,
     *     economy_child_under4_percent: float,
     *     economics_notification_email: string
     * }
     */
    public function defaults(): array
    {
        return [
            self::KEY_GUIDE_HOURLY_COST => 0.0,
            self::KEY_RESTAURANT_HOURLY_COST => 0.0,
            self::KEY_OB_HOURLY_AMOUNT => 0.0,
            self::KEY_PRICE_ADULT => 0.0,
            self::KEY_PRICE_YOUTH => 0.0,
            self::KEY_PRICE_CHILD => 0.0,
            self::KEY_CHILD_UNDER4_PERCENT => 0.0,
            self::KEY_NOTIFICATION_EMAIL => '',
        ];
    }

    /**
     * @return array{
     *     economy_guide_hourly_cost: float,
     *     economy_restaurant_hourly_cost: float,
     *     economy_ob_hourly_amount: float,
     *     economy_price_adult: float,
     *     economy_price_youth: float,
     *     economy_price_child: float,
     *     economy_child_under4_percent: float,
     *     economics_notification_email: string
     * }
     */
    public function all(): array
    {
        $defaults = $this->defaults();

        return [
            self::KEY_GUIDE_HOURLY_COST => $this->decimal(self::KEY_GUIDE_HOURLY_COST, $defaults[self::KEY_GUIDE_HOURLY_COST]),
            self::KEY_RESTAURANT_HOURLY_COST => $this->decimal(self::KEY_RESTAURANT_HOURLY_COST, $defaults[self::KEY_RESTAURANT_HOURLY_COST]),
            self::KEY_OB_HOURLY_AMOUNT => $this->decimal(self::KEY_OB_HOURLY_AMOUNT, $defaults[self::KEY_OB_HOURLY_AMOUNT]),
            self::KEY_PRICE_ADULT => $this->decimal(self::KEY_PRICE_ADULT, $defaults[self::KEY_PRICE_ADULT]),
            self::KEY_PRICE_YOUTH => $this->decimal(self::KEY_PRICE_YOUTH, $defaults[self::KEY_PRICE_YOUTH]),
            self::KEY_PRICE_CHILD => $this->decimal(self::KEY_PRICE_CHILD, $defaults[self::KEY_PRICE_CHILD]),
            self::KEY_CHILD_UNDER4_PERCENT => $this->decimal(self::KEY_CHILD_UNDER4_PERCENT, $defaults[self::KEY_CHILD_UNDER4_PERCENT]),
            self::KEY_NOTIFICATION_EMAIL => trim((string) setting(self::KEY_NOTIFICATION_EMAIL, $defaults[self::KEY_NOTIFICATION_EMAIL])),
        ];
    }

    /**
     * @param  array{
     *     economy_guide_hourly_cost?: mixed,
     *     economy_restaurant_hourly_cost?: mixed,
     *     economy_ob_hourly_amount?: mixed,
     *     economy_price_adult?: mixed,
     *     economy_price_youth?: mixed,
     *     economy_price_child?: mixed,
     *     economy_child_under4_percent?: mixed,
     *     economics_notification_email?: mixed
     * }  $data
     */
    public function update(array $data): void
    {
        $payload = [
            self::KEY_GUIDE_HOURLY_COST => $this->formatMoney($data[self::KEY_GUIDE_HOURLY_COST] ?? 0),
            self::KEY_RESTAURANT_HOURLY_COST => $this->formatMoney($data[self::KEY_RESTAURANT_HOURLY_COST] ?? 0),
            self::KEY_OB_HOURLY_AMOUNT => $this->formatMoney($data[self::KEY_OB_HOURLY_AMOUNT] ?? 0),
            self::KEY_PRICE_ADULT => $this->formatMoney($data[self::KEY_PRICE_ADULT] ?? 0),
            self::KEY_PRICE_YOUTH => $this->formatMoney($data[self::KEY_PRICE_YOUTH] ?? 0),
            self::KEY_PRICE_CHILD => $this->formatMoney($data[self::KEY_PRICE_CHILD] ?? 0),
            self::KEY_CHILD_UNDER4_PERCENT => $this->formatPercent($data[self::KEY_CHILD_UNDER4_PERCENT] ?? 0),
            self::KEY_NOTIFICATION_EMAIL => trim((string) ($data[self::KEY_NOTIFICATION_EMAIL] ?? '')),
        ];

        foreach ($payload as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }

    public function guideHourlyCost(): float
    {
        return $this->all()[self::KEY_GUIDE_HOURLY_COST];
    }

    public function restaurantHourlyCost(): float
    {
        return $this->all()[self::KEY_RESTAURANT_HOURLY_COST];
    }

    public function obHourlyAmount(): float
    {
        return $this->all()[self::KEY_OB_HOURLY_AMOUNT];
    }

    public function adultPrice(): float
    {
        return $this->all()[self::KEY_PRICE_ADULT];
    }

    public function youthPrice(): float
    {
        return $this->all()[self::KEY_PRICE_YOUTH];
    }

    public function childPrice(): float
    {
        return $this->all()[self::KEY_PRICE_CHILD];
    }

    public function childUnder4Percent(): float
    {
        return $this->all()[self::KEY_CHILD_UNDER4_PERCENT];
    }

    public function notificationEmail(): string
    {
        return $this->all()[self::KEY_NOTIFICATION_EMAIL];
    }

    /**
     * Andel betalande barn (0–1) efter avdrag för barn under 4.
     */
    public function payingChildRatio(): float
    {
        return max(0.0, min(1.0, 1 - ($this->childUnder4Percent() / 100)));
    }

    /**
     * Uppskattad biljettintäkt. Ospecificerade räknas som vuxenpris.
     */
    public function estimateRevenue(int $men, int $women, int $youth, int $child, int $unspecified = 0): float
    {
        $adults = max(0, $men) + max(0, $women) + max(0, $unspecified);
        $youthCount = max(0, $youth);
        $payingChildren = max(0, $child) * $this->payingChildRatio();

        return round(
            ($adults * $this->adultPrice())
            + ($youthCount * $this->youthPrice())
            + ($payingChildren * $this->childPrice()),
            2
        );
    }

    private function decimal(string $key, float $default): float
    {
        $raw = setting($key, $default);

        if ($raw === null || $raw === '') {
            return $default;
        }

        return round((float) $raw, 2);
    }

    private function formatMoney(mixed $value): string
    {
        return number_format(max(0, (float) $value), 2, '.', '');
    }

    private function formatPercent(mixed $value): string
    {
        $percent = max(0, min(100, (float) $value));

        return number_format($percent, 2, '.', '');
    }
}
