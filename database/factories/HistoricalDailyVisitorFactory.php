<?php

namespace Database\Factories;

use App\Models\HistoricalDailyVisitor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<HistoricalDailyVisitor>
 */
class HistoricalDailyVisitorFactory extends Factory
{
    public function definition(): array
    {
        $date = Carbon::parse(fake()->dateTimeBetween('-2 years', '-1 day'))->startOfDay();

        return [
            'stat_date' => $date->toDateString(),
            'participant_count' => fake()->numberBetween(0, 80),
            ...HistoricalDailyVisitor::isoAttributesFromDate($date),
            'imported_at' => now(),
        ];
    }

    public function onDate(string $date, int $participantCount): static
    {
        $carbon = Carbon::parse($date)->startOfDay();

        return $this->state(fn () => [
            'stat_date' => $carbon->toDateString(),
            'participant_count' => $participantCount,
            ...HistoricalDailyVisitor::isoAttributesFromDate($carbon),
        ]);
    }
}
