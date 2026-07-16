<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Variety;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Variety>
 */
class VarietyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->unique()->word()).' Apple',
            'origin' => $this->faker->country(),
            'user_id' => null,
        ];
    }

    /**
     * A custom variety owned by a specific (or new) user.
     */
    public function custom(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }
}
