<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    protected $model = Venue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->company().' '.fake()->randomElement(['Hall', 'Center', 'Convention Center']),
            'address' => fake()->streetAddress().', '.fake()->city(),
            'notes' => fake()->optional()->sentence(),
            'is_public' => true,
        ];
    }

    public function private(): static
    {
        return $this->state(['is_public' => false]);
    }
}
