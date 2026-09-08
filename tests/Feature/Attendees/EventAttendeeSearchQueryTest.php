<?php

namespace Tests\Feature\Attendees;

use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Queries\Attendees\EventAttendeeSearchQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventAttendeeSearchQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_attendees_does_not_query_per_row(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $org->memberships()->create([
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner,
            'joined_at' => now(),
        ]);
        $event = Event::factory()->for($org)->create();
        $ticketType = TicketType::factory()->free()->for($event)->create();

        $registrations = Registration::factory()->count(10)
            ->for($event)
            ->for($ticketType, 'ticketType')
            ->create();

        Ticket::factory()->for($registrations->first())->create();

        DB::enableQueryLog();

        $result = (new EventAttendeeSearchQuery)($event, []);

        $this->assertCount(10, $result->items());
        $this->assertSame(10, $result->total());
        $this->assertLessThanOrEqual(5, count(DB::getQueryLog()));
    }
}
