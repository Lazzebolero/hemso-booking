<?php

namespace Database\Factories;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    protected $model = TimeEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = Carbon::now()->subHours(3);

        return [
            'user_id' => User::factory(),
            'work_date' => $start->toDateString(),
            'clock_in_at_original' => $start,
            'clock_out_at_original' => null,
            'start_at' => $start,
            'end_at' => null,
            'break_minutes' => 0,
            'status' => TimeEntry::STATUS_OPEN,
            'user_comment' => null,
            'admin_comment' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(function (array $attributes) {
            $start = Carbon::parse($attributes['start_at']);
            $end = $start->copy()->addHours(2);

            return [
                'work_date' => $start->toDateString(),
                'clock_out_at_original' => $end,
                'end_at' => $end,
                'status' => TimeEntry::STATUS_DRAFT,
            ];
        });
    }

    public function approved(): static
    {
        return $this->draft()->state(fn () => [
            'status' => TimeEntry::STATUS_APPROVED,
        ]);
    }
}
