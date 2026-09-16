<?php

namespace Database\Factories;

use App\Models\FacilityMemory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FacilityMemory>
 */
class FacilityMemoryFactory extends Factory
{
    protected $model = FacilityMemory::class;

    public function definition(): array
    {
        return [
            'type' => FacilityMemory::TYPE_TEXT,
            'body' => fake()->paragraph(),
            'context_note' => null,
            'location_text' => fake()->optional()->randomElement(['Mansköket', 'Kasern 3', 'Bryggan']),
            'era_text' => fake()->optional()->randomElement(['1970-talet', 'ca 1973', '1980-talet']),
            'visitor_name' => null,
            'consent_type' => FacilityMemory::CONSENT_WRITTEN,
            'consent_given' => true,
            'collected_by' => User::factory(),
            'status' => FacilityMemory::STATUS_SUBMITTED,
        ];
    }

    public function audio(): static
    {
        return $this->state(fn () => [
            'type' => FacilityMemory::TYPE_AUDIO,
            'body' => null,
            'context_note' => fake()->sentence(),
            'consent_type' => FacilityMemory::CONSENT_RECORDED,
            'audio_path' => 'facility_memories/'.now()->format('Y/m').'/sample.webm',
            'audio_duration_seconds' => 125,
            'audio_mime_type' => 'audio/webm',
            'audio_size' => 1024,
        ]);
    }
}
