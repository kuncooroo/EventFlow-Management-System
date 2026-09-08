<?php

namespace Tests\Feature\Security;

use App\Actions\Registrations\RegisterAttendee;
use App\Actions\Tickets\IssueTicket;
use App\Livewire\Public\RegistrationForm;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TokenEntropyTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_code_is_unpredictable_random_hex(): void
    {
        $registration = Registration::factory()->confirmed()->create();

        $ticket = app(IssueTicket::class)->handle($registration);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{26}$/', $ticket->ticket_code);
        $this->assertNotSame((string) $registration->id, $ticket->ticket_code);
    }

    public function test_ticket_code_is_not_derived_from_anything_sequential(): void
    {
        $first = app(IssueTicket::class)->handle(Registration::factory()->confirmed()->create());
        $second = app(IssueTicket::class)->handle(Registration::factory()->confirmed()->create());

        $this->assertMatchesRegularExpression('/^[a-f0-9]{26}$/', $second->ticket_code);
        $this->assertNotSame($first->ticket_code, $second->ticket_code);
    }

    public function test_qr_token_keeps_256_bit_entropy(): void
    {
        $ticket = Ticket::factory()->create();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $ticket->qr_token);
    }

    public function test_registration_code_is_unpredictable_random_hex(): void
    {
        $event = Event::factory()->published()->create([
            'registration_enabled' => true,
            'registration_starts_at' => now()->subDay(),
            'registration_ends_at' => now()->addDays(30),
        ]);

        $registration = app(RegisterAttendee::class)->handle($event, [
            'attendee_name' => 'Rina Wulandari',
            'attendee_email' => 'rina@example.com',
            'attendee_phone' => null,
            'attendee_organization' => null,
            'ticket_type_id' => null,
            'answers' => [],
        ]);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{26}$/', $registration->registration_code);
    }

    public function test_public_ticket_page_serves_ticket_keyed_by_random_hex_code(): void
    {
        $event = Event::factory()->published()->create([
            'name' => 'Digital Marketing Workshop',
            'registration_enabled' => true,
            'registration_starts_at' => now()->subDay(),
            'registration_ends_at' => now()->addDays(30),
        ]);
        $ticketType = TicketType::factory()->free()->for($event)->create([
            'available_from' => now()->subDay(),
            'available_until' => now()->addDays(30),
        ]);

        Livewire::test(RegistrationForm::class, ['slug' => $event->public_slug])
            ->set('attendee_name', 'Rina Wulandari')
            ->set('attendee_email', 'rina@example.com')
            ->set('ticket_type_id', $ticketType->id)
            ->call('submit');

        $registration = Registration::query()->where('event_id', $event->id)->firstOrFail();
        $ticket = $registration->ticket;

        $this->assertMatchesRegularExpression('/^[a-f0-9]{26}$/', $ticket->ticket_code);

        $this->get(route('tickets.public.show', $ticket->ticket_code))
            ->assertOk()
            ->assertSee($ticket->ticket_code, false)
            ->assertSee('Rina Wulandari', false);
    }
}
