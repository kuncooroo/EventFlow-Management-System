<?php

namespace Tests\Feature\Tickets;

use App\Enums\RegistrationStatus;
use App\Livewire\Public\RegistrationForm;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Services\Tickets\TicketQrCodeService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class PublicTicketAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeTicket(): Ticket
    {
        $event = Event::factory()->create([
            'name' => 'Digital Marketing Workshop',
        ]);
        $ticketType = TicketType::factory()->free()->for($event)->create([
            'name' => 'General Ticket',
        ]);
        $registration = Registration::factory()->for($event)->for($ticketType, 'ticketType')->create([
            'attendee_name' => 'Rina Wulandari',
            'status' => RegistrationStatus::Confirmed,
        ]);

        return Ticket::factory()->for($registration)->create();
    }

    public function test_ticket_page_renders_ticket_qr_and_event_details(): void
    {
        $ticket = $this->makeTicket();
        $registration = $ticket->registration;

        $response = $this->get(route('tickets.public.show', $ticket->ticket_code));

        $response->assertOk();
        $response->assertSee('Digital Marketing Workshop', false);
        $response->assertSee('Rina Wulandari', false);
        $response->assertSee('General Ticket', false);
        $response->assertSee($ticket->ticket_code, false);
        $response->assertSee('<svg', false);
    }

    public function test_qr_payload_is_the_ticket_qr_token(): void
    {
        $qrCodes = $this->spy(TicketQrCodeService::class);
        $ticket = $this->makeTicket();

        $this->get(route('tickets.public.show', $ticket->ticket_code))->assertOk();

        $qrCodes->shouldHaveReceived('svg')->once()->with($ticket->qr_token);
    }

    public function test_unknown_ticket_code_returns_404(): void
    {
        $this->get(route('tickets.public.show', str_repeat('A', 26)))->assertNotFound();
    }

    public function test_malformed_token_returns_404(): void
    {
        $this->get(route('tickets.public.show', 'short'))->assertNotFound();
        $this->get(route('tickets.public.show', str_repeat('A', 25)))->assertNotFound();
    }

    public function test_sequential_id_guessing_returns_404(): void
    {
        $ticket = $this->makeTicket();

        $this->get(route('tickets.public.show', '1'))->assertNotFound();
        $this->get(route('tickets.public.show', '2'))->assertNotFound();
    }

    public function test_cancelled_registration_ticket_page_shows_invalid_notice(): void
    {
        $ticket = $this->makeTicket();
        $ticket->registration->update([
            'status' => RegistrationStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        $response = $this->get(route('tickets.public.show', $ticket->ticket_code));

        $response->assertOk();
        $response->assertSee('This registration was cancelled.', false);
    }

    public function test_ticket_lookup_is_rate_limited(): void
    {
        RateLimiter::for('tickets', fn () => Limit::perMinute(2));
        $ticket = $this->makeTicket();

        $this->get(route('tickets.public.show', $ticket->ticket_code))->assertOk();
        $this->get(route('tickets.public.show', $ticket->ticket_code))->assertOk();

        $this->get(route('tickets.public.show', $ticket->ticket_code))->assertStatus(429);
    }

    public function test_registration_confirmation_links_to_ticket_page(): void
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

        $component = Livewire::test(RegistrationForm::class, ['slug' => $event->public_slug])
            ->set('attendee_name', 'Rina Wulandari')
            ->set('attendee_email', 'rina@test.com')
            ->set('ticket_type_id', $ticketType->id)
            ->call('submit');

        $registration = Registration::query()->where('event_id', $event->id)->firstOrFail();

        $component->assertSee('View Ticket', false);
        $component->assertSee(route('tickets.public.show', $registration->ticket->ticket_code), false);
    }
}
