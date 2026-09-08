<?php

namespace Tests\Feature\Public;

use App\Enums\EventStatus;
use App\Models\AgendaItem;
use App\Models\Event;
use App\Models\MediaFile;
use App\Models\Organization;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventPageTest extends TestCase
{
    use RefreshDatabase;

    private function makePublishedEvent(array $overrides = []): Event
    {
        $org = Organization::factory()->create();

        return Event::factory()->for($org)->published()->create(array_merge([
            'organizer_name' => 'OpenTech',
            'contact_name' => 'Anisa',
            'contact_email' => 'anisa@test.com',
            'mode' => 'offline',
        ], $overrides));
    }

    // ─── Visibility ────────────────────────────────────────────────────────

    public function test_published_event_is_accessible(): void
    {
        $event = $this->makePublishedEvent(['name' => 'Laracon']);

        $this->get(route('public.events.show', $event->public_slug))
            ->assertOk()
            ->assertSee('Laracon')
            ->assertSee('OpenTech');
    }

    public function test_draft_event_returns_404(): void
    {
        $event = Event::factory()->create(['public_slug' => 'draft-slug']);

        $this->get(route('public.events.show', 'draft-slug'))->assertNotFound();
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->get(route('public.events.show', 'nonexistent-slug'))->assertNotFound();
    }

    public function test_archived_event_returns_404(): void
    {
        $event = $this->makePublishedEvent([
            'status' => EventStatus::Archived,
            'public_slug' => 'archived-event',
        ]);

        $this->get(route('public.events.show', 'archived-event'))->assertNotFound();
    }

    // ─── CTA States ────────────────────────────────────────────────────────

    public function test_open_registration_shows_register_now(): void
    {
        $event = $this->makePublishedEvent([
            'registration_enabled' => true,
            'registration_starts_at' => now()->subDay(),
            'registration_ends_at' => now()->addDays(30),
        ]);

        $this->get(route('public.events.show', $event->public_slug))
            ->assertOk()
            ->assertSee('Register Now');
    }

    public function test_disabled_registration_shows_closed(): void
    {
        $event = $this->makePublishedEvent(['registration_enabled' => false]);

        $this->get(route('public.events.show', $event->public_slug))
            ->assertOk()
            ->assertSee('Registration Closed')
            ->assertDontSee('Register Now');
    }

    public function test_scheduled_registration_shows_opens_on_date(): void
    {
        $futureDate = Carbon::now()->addDays(14)->startOfDay()->addHours(9);
        $event = $this->makePublishedEvent([
            'registration_enabled' => true,
            'registration_starts_at' => $futureDate,
            'registration_ends_at' => $futureDate->copy()->addMonth(),
        ]);

        $response = $this->get(route('public.events.show', $event->public_slug));

        $response->assertOk()
            ->assertSee('Registration opens on')
            ->assertSee($futureDate->format('M j, Y g:i A'))
            ->assertDontSee('Register Now');
    }

    public function test_ended_registration_shows_closed(): void
    {
        $event = $this->makePublishedEvent([
            'registration_enabled' => true,
            'registration_starts_at' => now()->subDays(60),
            'registration_ends_at' => now()->subDay(),
        ]);

        $this->get(route('public.events.show', $event->public_slug))
            ->assertOk()
            ->assertSee('Registration Closed')
            ->assertSee('ended on')
            ->assertDontSee('Register Now');
    }

    public function test_sold_out_event_shows_sold_out(): void
    {
        $event = $this->makePublishedEvent([
            'registration_enabled' => true,
            'registration_starts_at' => now()->subDay(),
            'registration_ends_at' => now()->addDays(30),
            'capacity' => 0,
        ]);

        $this->get(route('public.events.show', $event->public_slug))
            ->assertOk()
            ->assertSee('Sold Out')
            ->assertSee('All spots')
            ->assertDontSee('Register Now');
    }

    public function test_cancelled_event_shows_cancelled_alert(): void
    {
        $event = $this->makePublishedEvent([
            'status' => EventStatus::Cancelled,
            'public_slug' => 'cancelled-event',
            'cancelled_at' => now(),
        ]);

        $this->get(route('public.events.show', 'cancelled-event'))
            ->assertOk()
            ->assertSee('This event has been cancelled.')
            ->assertSee('Registration is closed')
            ->assertDontSee('Register Now');
    }

    // ─── Venue / Online ────────────────────────────────────────────────────

    public function test_public_venue_is_shown(): void
    {
        $event = $this->makePublishedEvent();
        Venue::factory()->for($event)->create([
            'name' => 'Main Hall',
            'address' => '123 Main St',
            'is_public' => true,
        ]);

        $this->get(route('public.events.show', $event->public_slug))
            ->assertOk()
            ->assertSee('Main Hall')
            ->assertSee('123 Main St');
    }

    public function test_private_venue_is_not_shown(): void
    {
        $event = $this->makePublishedEvent();
        Venue::factory()->for($event)->private()->create([
            'name' => 'Secret Room',
            'address' => '456 Secret St',
        ]);

        $this->get(route('public.events.show', $event->public_slug))
            ->assertOk()
            ->assertDontSee('Secret Room')
            ->assertDontSee('456 Secret St');
    }

    public function test_online_event_shows_online(): void
    {
        $event = $this->makePublishedEvent(['mode' => 'online']);

        $this->get(route('public.events.show', $event->public_slug))
            ->assertOk()
            ->assertSee('Online event');
    }

    // ─── Agenda / Tickets / Org data ───────────────────────────────────────

    public function test_agenda_shows_chronologically(): void
    {
        $event = $this->makePublishedEvent();
        AgendaItem::factory()->for($event)->create([
            'title' => 'Morning Talk',
            'sort_order' => 0,
            'start_at' => now()->addDay()->setTime(9, 0),
            'end_at' => now()->addDay()->setTime(10, 0),
        ]);
        AgendaItem::factory()->for($event)->create([
            'title' => 'Afternoon Workshop',
            'sort_order' => 1,
            'start_at' => now()->addDay()->setTime(14, 0),
            'end_at' => now()->addDay()->setTime(16, 0),
        ]);

        $response = $this->get(route('public.events.show', $event->public_slug));
        $html = $response->content();

        $morningPos = strpos($html, 'Morning Talk');
        $afternoonPos = strpos($html, 'Afternoon Workshop');
        $this->assertNotFalse($morningPos);
        $this->assertNotFalse($afternoonPos);
        $this->assertLessThan($afternoonPos, $morningPos);
    }

    public function test_ticket_types_shown_when_available(): void
    {
        $event = $this->makePublishedEvent();
        $event->ticketTypes()->create([
            'name' => 'General',
            'description' => 'Standard ticket',
            'price_amount' => 50.00,
            'currency' => 'USD',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->get(route('public.events.show', $event->public_slug))
            ->assertOk()
            ->assertSee('General')
            ->assertSee('Standard ticket')
            ->assertSee('50.00')
            ->assertSee('USD');
    }

    public function test_inactive_ticket_type_hidden(): void
    {
        $event = $this->makePublishedEvent();
        $event->ticketTypes()->create([
            'name' => 'Old Pass',
            'is_active' => false,
            'sort_order' => 0,
        ]);

        $this->get(route('public.events.show', $event->public_slug))
            ->assertOk()
            ->assertDontSee('Old Pass');
    }

    public function test_zero_ticket_types_still_renders(): void
    {
        $event = $this->makePublishedEvent();

        $this->get(route('public.events.show', $event->public_slug))
            ->assertOk()
            ->assertSee($event->name);
    }

    public function test_venue_notes_not_leaked(): void
    {
        $event = $this->makePublishedEvent();
        Venue::factory()->for($event)->create([
            'name' => 'Convention Hall',
            'notes' => 'Internal backstage access code: 1234',
            'is_public' => true,
        ]);

        $this->get(route('public.events.show', $event->public_slug))
            ->assertOk()
            ->assertDontSee('backstage access code');
    }

    public function test_banner_shown_when_present(): void
    {
        Storage::fake('public');
        $event = $this->makePublishedEvent();
        MediaFile::factory()->banner($event)->create();

        $this->get(route('public.events.show', $event->public_slug))
            ->assertOk()
            ->assertSee('banner');
    }

    public function test_description_shown_when_available(): void
    {
        $event = $this->makePublishedEvent([
            'description' => 'Join us for a full-day conference.',
        ]);

        $this->get(route('public.events.show', $event->public_slug))
            ->assertOk()
            ->assertSee('Join us for a full-day conference.');
    }

    public function test_organizer_contact_displayed(): void
    {
        $event = $this->makePublishedEvent([
            'contact_name' => 'Anisa',
            'contact_email' => 'anisa@test.com',
            'contact_phone' => '+6281234',
        ]);

        $this->get(route('public.events.show', $event->public_slug))
            ->assertOk()
            ->assertSee('Anisa')
            ->assertSee('anisa@test.com')
            ->assertSee('+6281234');
    }

    public function test_private_venue_hidden_does_not_leak_id(): void
    {
        $event = $this->makePublishedEvent();
        Venue::factory()->for($event)->private()->create([
            'name' => 'Secret',
        ]);

        $this->get(route('public.events.show', $event->public_slug))
            ->assertOk()
            ->assertDontSee('Secret');
    }
}
