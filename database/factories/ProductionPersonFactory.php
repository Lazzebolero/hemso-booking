<?php

namespace Database\Factories;

use App\Models\Production;
use App\Models\ProductionPerson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionPerson>
 */
class ProductionPersonFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'production_id' => Production::factory(),
            'user_id' => null,
            'name' => fake()->name(),
            'kind' => ProductionPerson::KIND_PARTICIPANT,
            'is_inside' => false,
            'sort_order' => 1,
        ];
    }
}
