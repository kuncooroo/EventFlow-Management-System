<?php

namespace Tests\Feature\Attendees;

use App\Enums\OrganizationRole;
use App\Enums\RegistrationStatus;
use App\Livewire\Attendees\AttendeeIndex;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttendeeIndexTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function makeOrgWithMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $membership = $org->memberships()->create([
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => now(),
        ]);
        session(['current_organization_id' => $org->id]);

        return [$user, $org, $membership];
    }

    // ─── Access ─────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $event = Event::factory()->create();

        $this->get(route('app.events.attendees.index', $event))
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_view_attendee_list(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['name' => 'Attendee Event']);
        Registration::factory()->for($event)->create(['attendee_name' => 'Anisa Halim', 'attendee_email' => 'anisa@example.com']);

        $this->actingAs($owner)
            ->get(route('app.events.attendees.index', $event))
            ->assertOk()
            ->assertSee('Attendee Event')
            ->assertSee('Anisa Halim')
            ->assertSee('anisa@example.com');
    }

    public function test_assigned_viewer_can_view_attendee_list(): void
    {
        [$viewer, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::Viewer);
        $event = Event::factory()->for($org)->create();

        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('app.events.attendees.index', $event))
            ->assertOk();
    }

    public function test_unassigned_staff_cannot_view_attendee_list(): void
    {
        [$staff, $org] = $this->makeOrgWithMember(OrganizationRole::Staff);
        $event = Event::factory()->for($org)->create();

        $this->actingAs($staff)
            ->get(route('app.events.attendees.index', $event))
            ->assertForbidden();
    }

    public function test_cross_org_attendee_list_is_blocked(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $otherOrg = Organization::factory()->create();
        $otherEvent = Event::factory()->for($otherOrg)->create();

        $this->actingAs($owner)
            ->get(route('app.events.attendees.index', $otherEvent))
            ->assertNotFound();
    }

    // ─── Search ─────────────────────────────────────────────────────────────

    public function test_search_filters_by_name_email_and_registration_code(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        Registration::factory()->for($event)->create(['attendee_name' => 'Grace Gomez']);
        Registration::factory()->for($event)->create(['attendee_email' => 'lee@example.com']);
        $other = Registration::factory()->for($event)->create(['attendee_name' => 'Sam Park', 'attendee_email' => 'sam@example.com']);

        Livewire::actingAs($owner)
            ->test(AttendeeIndex::class, ['event' => $event])
            ->set('search', 'Grace')
            ->assertSee('Grace Gomez')
            ->assertDontSee('Sam Park');

        Livewire::actingAs($owner)
            ->test(AttendeeIndex::class, ['event' => $event])
            ->set('search', 'lee@example.com')
            ->assertSee('lee@example.com')
            ->assertDontSee('sam@example.com');

        Livewire::actingAs($owner)
            ->test(AttendeeIndex::class, ['event' => $event])
            ->set('search', $other->registration_code)
            ->assertSee('Sam Park')
            ->assertDontSee('Grace Gomez');
    }

    public function test_search_does_not_return_another_events_attendees(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['name' => 'Target Event']);
        $otherEvent = Event::factory()->for($org)->create(['name' => 'Other Event']);
        Registration::factory()->for($otherEvent)->create(['attendee_name' => 'Nadia Sameja']);
        Registration::factory()->for($event)->create(['attendee_name' => 'Rina Mim']);

        Livewire::actingAs($owner)
            ->test(AttendeeIndex::class, ['event' => $event])
            ->set('search', 'Sameja')
            ->assertDontSee('Nadia Sameja')
            ->assertSee('No attendees match your filters.');
    }

    // ─── Filters ────────────────────────────────────────────────────────────

    public function test_status_filter_returns_only_matching_registrations(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        Registration::factory()->for($event)->confirmed()->create(['attendee_name' => 'Tara One']);
        Registration::factory()->for($event)->cancelled()->create(['attendee_name' => 'Carla Two']);

        Livewire::actingAs($owner)
            ->test(AttendeeIndex::class, ['event' => $event])
            ->set('statusFilter', RegistrationStatus::Cancelled->value)
            ->assertSee('Carla Two')
            ->assertDontSee('Tara One');
    }

    public function test_ticket_type_filter_returns_only_matching_registrations(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $vip = TicketType::factory()->free()->for($event)->create(['name' => 'VIP']);
        $general = TicketType::factory()->free()->for($event)->create(['name' => 'General']);
        Registration::factory()->for($event)->for($vip, 'ticketType')->create(['attendee_name' => 'Vip Only']);
        Registration::factory()->for($event)->for($general, 'ticketType')->create(['attendee_name' => 'General Entry']);

        Livewire::actingAs($owner)
            ->test(AttendeeIndex::class, ['event' => $event])
            ->set('ticketTypeFilter', (string) $vip->id)
            ->assertSee('Vip Only')
            ->assertDontSee('General Entry');
    }

    public function test_registration_date_range_filter_returns_only_matching_registrations(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        Registration::factory()->for($event)->create(['attendee_name' => 'Older One', 'registered_at' => now()->subDays(5)]);
        Registration::factory()->for($event)->create(['attendee_name' => 'Newer Two', 'registered_at' => now()->subDay()]);

        Livewire::actingAs($owner)
            ->test(AttendeeIndex::class, ['event' => $event])
            ->set('dateFrom', now()->subDays(2)->format('Y-m-d'))
            ->assertSee('Newer Two')
            ->assertDontSee('Older One');
    }

    public function test_checked_in_filter_returns_no_rows_until_check_ins_exist(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        Registration::factory()->for($event)->count(3)->create(['attendee_name' => 'Any Attendee']);

        Livewire::actingAs($owner)
            ->test(AttendeeIndex::class, ['event' => $event])
            ->set('checkedInFilter', '1')
            ->assertSee('No attendees match your filters.');

        Livewire::actingAs($owner)
            ->test(AttendeeIndex::class, ['event' => $event])
            ->set('checkedInFilter', '0')
            ->assertSee('Any Attendee');
    }

    public function test_clear_filters_restores_full_list(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        Registration::factory()->for($event)->count(2)->create(['attendee_name' => 'Tara Show']);

        Livewire::actingAs($owner)
            ->test(AttendeeIndex::class, ['event' => $event])
            ->set('search', 'no-match')
            ->assertSee('No attendees match your filters.')
            ->call('clearFilters')
            ->assertSee('Tara Show')
            ->assertDontSee('No attendees match your filters.');
    }

    // ─── Pagination ─────────────────────────────────────────────────────────

    public function test_attendee_list_paginates_in_per_page_sets(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        foreach (range(1, 20) as $i) {
            Registration::factory()->for($event)->create([
                'attendee_name' => sprintf('Attendee %02d', $i),
                'registered_at' => now()->subMinutes($i),
            ]);
        }

        $component = Livewire::actingAs($owner)->test(AttendeeIndex::class, ['event' => $event]);

        $component->assertSee('Attendee 01');
        $component->assertSee('Attendee 15');
        $component->assertDontSee('Attendee 16');

        $component->call('setPage', 2);

        $component->assertSee('Attendee 20');
        $component->assertDontSee('Attendee 01');
    }
}
