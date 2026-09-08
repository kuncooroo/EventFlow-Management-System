<?php

namespace Database\Factories;

use App\Models\AgendaItem;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgendaItem>
 */
class AgendaItemFactory extends Factory
{
    protected $model = AgendaItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'title' => fake()->sentence(3),
            'start_at' => fake()->dateTimeBetween('+1 week', '+2 weeks'),
            'end_at' => fake()->dateTimeBetween('+2 weeks', '+3 weeks'),
            'location' => fake()->optional()->city(),
            'speaker_text' => fake()->optional()->name(),
            'description' => fake()->optional()->paragraph(),
            'sort_order' => 0,
        ];
    }
}
