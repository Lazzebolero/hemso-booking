<?php

namespace Database\Factories;

use App\Models\StatisticsDayNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatisticsDayNote>
 */
class StatisticsDayNoteFactory extends Factory
{
    protected $model = StatisticsDayNote::class;

    public function definition(): array
    {
        return [
            'note_date' => fake()->unique()->date(),
            'body' => fake()->sentence(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
