<?php

namespace Database\Factories;

use App\Models\LoginEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoginEvent>
 */
class LoginEventFactory extends Factory
{
    protected $model = LoginEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'email' => fake()->safeEmail(),
            'event_type' => 'failed',
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'occurred_at' => now(),
        ];
    }

    public function login(?User $user = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user?->id,
            'email' => $user?->email ?? $attributes['email'] ?? fake()->safeEmail(),
            'event_type' => 'login',
        ]);
    }

    public function failed(?string $email = null): static
    {
        return $this->state(fn (): array => [
            'user_id' => null,
            'email' => $email ?? fake()->safeEmail(),
            'event_type' => 'failed',
        ]);
    }
}
