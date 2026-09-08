<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 week', '+6 months');
        $end = fake()->dateTimeBetween($start, '+1 year');

        return [
            'organization_id' => Organization::factory(),
            'created_by_user_id' => null,
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status' => EventStatus::Draft,
            'mode' => fake()->randomElement(['online', 'offline', 'hybrid']),
            'start_at' => $start,
            'end_at' => $end,
            'registration_enabled' => false,
            'capacity' => null,
            'require_phone' => false,
            'require_organization' => false,
            'reminder_enabled' => false,
            'reminder_hours_before' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => EventStatus::Draft]);
    }

    public function published(): static
    {
        return $this->state([
            'status' => EventStatus::Published,
            'published_at' => now(),
            'public_slug' => fake()->unique()->slug(),
        ]);
    }

    public function publishReady(): static
    {
        return $this->state([
            'status' => EventStatus::Draft,
            'name' => 'Ready Event',
            'organizer_name' => 'Test Organizer',
            'start_at' => now()->addDays(5),
            'end_at' => now()->addDays(6),
        ]);
    }

    public function ongoing(): static
    {
        return $this->state([
            'status' => EventStatus::Ongoing,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => EventStatus::Completed,
            'started_at' => now()->subDay(),
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => EventStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state([
            'status' => EventStatus::Archived,
            'archived_at' => now(),
        ]);
    }
}
