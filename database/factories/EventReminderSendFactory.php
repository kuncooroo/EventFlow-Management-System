<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventReminderSend;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventReminderSend>
 */
class EventReminderSendFactory extends Factory
{
    protected $model = EventReminderSend::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'occurrence_key' => 'reminder:'.fake()->unique()->numberBetween(100000, 9999999),
            'sent_at' => now(),
            'created_at' => now(),
        ];
    }
}
