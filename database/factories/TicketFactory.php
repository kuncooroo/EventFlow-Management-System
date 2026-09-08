<?php

namespace Database\Factories;

use App\Models\Registration;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'ticket_code' => bin2hex(random_bytes(13)),
            'qr_token' => bin2hex(random_bytes(32)),
            'issued_at' => now(),
        ];
    }
}
