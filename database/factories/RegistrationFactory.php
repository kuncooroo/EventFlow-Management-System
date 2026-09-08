<?php

namespace Database\Factories;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'ticket_type_id' => null,
            'registration_code' => bin2hex(random_bytes(13)),
            'status' => RegistrationStatus::Confirmed,
            'attendee_name' => fake()->name(),
            'attendee_email' => fake()->unique()->safeEmail(),
            'attendee_phone' => null,
            'attendee_organization' => null,
            'registered_at' => now(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['status' => RegistrationStatus::Confirmed]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => RegistrationStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
