<?php

namespace Database\Factories;

use App\Models\LockedPayrollPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LockedPayrollPeriod>
 */
class LockedPayrollPeriodFactory extends Factory
{
    protected $model = LockedPayrollPeriod::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->subDays(10)->startOfDay();
        $end = now()->subDays(5)->startOfDay();

        return [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'locked_by' => User::factory(),
            'locked_at' => now(),
        ];
    }
}
