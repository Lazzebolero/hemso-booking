<?php

namespace Database\Factories;

use App\Models\PostalCodeCollectionDay;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostalCodeCollectionDay>
 */
class PostalCodeCollectionDayFactory extends Factory
{
    protected $model = PostalCodeCollectionDay::class;

    public function definition(): array
    {
        return [
            'collection_date' => fake()->unique()->date(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
