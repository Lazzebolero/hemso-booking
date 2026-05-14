<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VisitorDog;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisitorDog>
 */
class VisitorDogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'dog_name' => fake()->firstName(),
            'breed' => fake()->optional()->word(),
            'owner_phone' => fake()->optional()->numerify('07########'),
            'visit_date' => now()->toDateString(),
            'tour_start_time' => null,
            'photo_path' => null,
            'registered_by' => User::factory(),
            'registered_as_role' => Roles::GUIDE,
        ];
    }
}
