<?php

namespace Database\Factories;

use App\Models\AppleCatch;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppleCatch>
 */
class AppleCatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'variety_id' => Variety::factory(),
            'caught_at' => $this->faker->dateTimeBetween('-1 year', 'today')->format('Y-m-d'),
            'lat' => null,
            'lng' => null,
            'location_label' => $this->faker->optional()->city(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
