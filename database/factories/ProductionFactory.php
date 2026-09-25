<?php

namespace Database\Factories;

use App\Models\Production;
use App\Support\ProductionSites;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Production>
 */
class ProductionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'TV-produktion',
            'sites' => [ProductionSites::STORRABERGET],
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addDays(7)->toDateString(),
            'is_active' => true,
        ];
    }
}
