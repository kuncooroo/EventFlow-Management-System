<?php

namespace Database\Factories;

use App\Enums\CheckInMethod;
use App\Models\CheckIn;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckIn>
 */
class CheckInFactory extends Factory
{
    protected $model = CheckIn::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'ticket_id' => null,
            'operator_user_id' => User::factory(),
            'method' => CheckInMethod::Manual,
            'checked_in_at' => now(),
            'created_at' => now(),
        ];
    }

    public function viaQr(): static
    {
        return $this->state(['method' => CheckInMethod::Qr]);
    }
}
