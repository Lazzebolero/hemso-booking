<?php

namespace Database\Factories;

use App\Models\PostalCodeCollectionDay;
use App\Models\PostalCodeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostalCodeEntry>
 */
class PostalCodeEntryFactory extends Factory
{
    protected $model = PostalCodeEntry::class;

    public function definition(): array
    {
        return [
            'postal_code_collection_day_id' => PostalCodeCollectionDay::factory(),
            'postal_code' => fake()->numerify('#####'),
            'people_count' => fake()->numberBetween(1, 8),
            'locality' => fake()->city(),
            'municipality_name' => 'Härnösand',
            'county_code' => '22',
            'county_name' => 'Västernorrlands län',
            'lookup_matched' => true,
        ];
    }
}
