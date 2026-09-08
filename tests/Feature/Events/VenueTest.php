<?php

namespace Tests\Feature\Events;

use App\Enums\EventStatus;
use App\Enums\OrganizationRole;
use App\Enums\VenueMode;
use App\Livewire\Events\VenueForm;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VenueTest extends TestCase
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

    // ─── Venue upsert ──────────────────────────────────────────────────────

    public function test_owner_can_set_offline_venue_details(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['name' => 'Venue Event']);

        Livewire::actingAs($owner)
            ->test(VenueForm::class, ['event' => $event])
            ->set('mode', VenueMode::Offline->value)
            ->set('name', 'Main Hall')
            ->set('address', 'Jl. Sudirman 1, Jakarta')
            ->set('notes', 'Loading dock at rear')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('events', ['id' => $event->id, 'mode' => VenueMode::Offline->value]);
        $this->assertDatabaseHas('venues', [
            'event_id' => $event->id,
            'name' => 'Main Hall',
            'address' => 'Jl. Sudirman 1, Jakarta',
            'notes' => 'Loading dock at rear',
            'is_public' => true,
        ]);
    }

    public function test_owner_can_update_an_existing_venue(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['mode' => VenueMode::Offline->value]);
        Venue::factory()->for($event)->create(['name' => 'Old Hall']);

        Livewire::actingAs($owner)
            ->test(VenueForm::class, ['event' => $event])
            ->assertSet('mode', VenueMode::Offline->value)
            ->assertSet('name', 'Old Hall')
            ->set('name', 'Renamed Hall')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('venues', 1);
        $this->assertDatabaseHas('venues', ['event_id' => $event->id, 'name' => 'Renamed Hall']);
    }

    public function test_switching_to_online_clears_existing_venue(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['mode' => VenueMode::Offline->value]);
        Venue::factory()->for($event)->create(['name' => 'Old Hall']);

        Livewire::actingAs($owner)
            ->test(VenueForm::class, ['event' => $event])
            ->set('mode', VenueMode::Online->value)
            ->set('name', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('events', ['id' => $event->id, 'mode' => VenueMode::Online->value]);
        $this->assertDatabaseCount('venues', 0);
    }

    public function test_offline_venue_requires_name(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['mode' => VenueMode::Online->value]);

        Livewire::actingAs($owner)
            ->test(VenueForm::class, ['event' => $event])
            ->set('mode', VenueMode::Offline->value)
            ->set('name', '')
            ->call('save')
            ->assertHasErrors('name');

        $this->assertDatabaseHas('events', ['id' => $event->id, 'mode' => VenueMode::Online->value]);
        $this->assertDatabaseCount('venues', 0);
    }

    public function test_hybrid_venue_allows_optional_address(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(VenueForm::class, ['event' => $event])
            ->set('mode', VenueMode::Hybrid->value)
            ->set('name', 'Hybrid Hub')
            ->set('address', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('venues', [
            'event_id' => $event->id,
            'name' => 'Hybrid Hub',
            'address' => null,
        ]);
    }

    public function test_venue_can_be_marked_private(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(VenueForm::class, ['event' => $event])
            ->set('mode', VenueMode::Offline->value)
            ->set('name', 'Private Hall')
            ->set('is_public', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('venues', ['event_id' => $event->id, 'is_public' => false]);
    }

    // ─── Status guard ──────────────────────────────────────────────────────

    public function test_published_event_cannot_have_venue_configured(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['status' => EventStatus::Published]);

        Livewire::actingAs($owner)
            ->test(VenueForm::class, ['event' => $event])
            ->set('mode', VenueMode::Offline->value)
            ->set('name', 'Too Late Hall')
            ->call('save')
            ->assertHasErrors('status');

        $this->assertDatabaseCount('venues', 0);
    }

    // ─── Authorization ─────────────────────────────────────────────────────

    public function test_assigned_event_manager_can_save_venue(): void
    {
        [$manager, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::EventManager);
        $event = Event::factory()->for($org)->create();

        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        Livewire::actingAs($manager)
            ->test(VenueForm::class, ['event' => $event])
            ->set('mode', VenueMode::Offline->value)
            ->set('name', 'Managed Venue')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('venues', ['event_id' => $event->id, 'name' => 'Managed Venue']);
    }

    public function test_unassigned_staff_cannot_save_venue(): void
    {
        [$staff, $org] = $this->makeOrgWithMember(OrganizationRole::Staff);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($staff)
            ->test(VenueForm::class, ['event' => $event])
            ->assertStatus(403);
    }

    public function test_cross_org_venue_is_blocked(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $otherOrg = Organization::factory()->create();
        $otherEvent = Event::factory()->for($otherOrg)->create();

        Livewire::actingAs($owner)
            ->test(VenueForm::class, ['event' => $otherEvent])
            ->assertStatus(403);
    }
}
