<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketType>
 */
class TicketTypeFactory extends Factory
{
    protected $model = TicketType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->randomElement(['General', 'Early Bird', 'VIP', 'Student', 'Free']),
            'description' => fake()->optional()->sentence(),
            'price_amount' => fake()->randomFloat(2, 0, 150),
            'currency' => 'USD',
            'capacity' => fake()->optional()->numberBetween(10, 1000),
            'available_from' => fake()->optional()->dateTimeBetween('-1 month', '-1 day'),
            'available_until' => fake()->optional()->dateTimeBetween('+1 day', '+2 months'),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function free(): static
    {
        return $this->state([
            'price_amount' => 0.00,
            'currency' => null,
        ]);
    }

    public function capped(int $capacity): static
    {
        return $this->state(['capacity' => $capacity]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
