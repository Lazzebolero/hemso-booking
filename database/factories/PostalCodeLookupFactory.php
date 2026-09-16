<?php

namespace Database\Factories;

use App\Models\PostalCodeLookup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostalCodeLookup>
 */
class PostalCodeLookupFactory extends Factory
{
    protected $model = PostalCodeLookup::class;

    public function definition(): array
    {
        return [
            'postal_code' => fake()->unique()->numerify('#####'),
            'locality' => fake()->city(),
            'municipality_code' => '2280',
            'municipality_name' => 'Härnösand',
            'county_code' => '22',
            'county_name' => 'Västernorrlands län',
        ];
    }
}
