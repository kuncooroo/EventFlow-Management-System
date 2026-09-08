<?php

namespace Tests\Feature\Tickets;

use App\Actions\Tickets\IssueTicket;
use App\Enums\RegistrationStatus;
use App\Models\Registration;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class IssueTicketTest extends TestCase
{
    use RefreshDatabase;

    private function makeRegistration(array $overrides = []): Registration
    {
        return Registration::factory()->create(array_merge([
            'status' => RegistrationStatus::Confirmed,
        ], $overrides));
    }

    public function test_issue_creates_unique_entropy_safe_tokens(): void
    {
        $registration = $this->makeRegistration();

        $ticket = app(IssueTicket::class)->handle($registration);

        $this->assertSame(26, strlen($ticket->ticket_code));
        $this->assertSame(64, strlen($ticket->qr_token));
        $this->assertNotSame((string) $registration->id, $ticket->ticket_code);
        $this->assertNotNull($ticket->issued_at);
    }

    public function test_two_registrations_get_distinct_ticket_codes_and_qr_tokens(): void
    {
        $first = $this->makeRegistration();
        $second = $this->makeRegistration();

        $firstTicket = app(IssueTicket::class)->handle($first);
        $secondTicket = app(IssueTicket::class)->handle($second);

        $this->assertNotSame($firstTicket->ticket_code, $secondTicket->ticket_code);
        $this->assertNotSame($firstTicket->qr_token, $secondTicket->qr_token);
        $this->assertSame(2, Ticket::count());
    }

    public function test_reissue_returns_the_same_ticket(): void
    {
        $registration = $this->makeRegistration();

        $first = app(IssueTicket::class)->handle($registration);
        $second = app(IssueTicket::class)->handle($registration);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Ticket::count());
    }

    public function test_cancelled_registration_does_not_get_new_ticket(): void
    {
        $registration = $this->makeRegistration([
            'status' => RegistrationStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        app(IssueTicket::class)->handle($registration);

        $this->assertSame(0, Ticket::count());
    }
}
