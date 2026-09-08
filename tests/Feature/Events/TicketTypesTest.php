<?php

namespace Tests\Feature\Events;

use App\Actions\Events\ReorderTicketTypes;
use App\Enums\OrganizationRole;
use App\Livewire\Events\EventSetup;
use App\Livewire\Events\TicketTypeManager;
use App\Models\Event;
use App\Models\Organization;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class TicketTypesTest extends TestCase
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

    // ─── Create ─────────────────────────────────────────────────────────────

    public function test_owner_can_create_free_ticket_type(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['name' => 'Tickets Event']);

        Livewire::actingAs($owner)
            ->test(TicketTypeManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('name', 'General Admission')
            ->set('price_amount', '0')
            ->set('currency', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ticket_types', [
            'event_id' => $event->id,
            'name' => 'General Admission',
            'price_amount' => '0.00',
            'currency' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_ticket_type_with_informational_price_stores_currency(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(TicketTypeManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('name', 'VIP')
            ->set('price_amount', '49.99')
            ->set('currency', 'usd')
            ->call('save')
            ->assertHasNoErrors();

        $ticketType = $event->ticketTypes()->first();
        $this->assertSame('49.99', (string) $ticketType->price_amount);
        $this->assertSame('USD', $ticketType->currency);
    }

    public function test_price_without_currency_is_rejected(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(TicketTypeManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('name', 'VIP')
            ->set('price_amount', '49.99')
            ->set('currency', '')
            ->call('save')
            ->assertHasErrors(['currency']);

        $this->assertDatabaseMissing('ticket_types', ['event_id' => $event->id]);
    }

    public function test_name_is_required(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(TicketTypeManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_negative_capacity_is_rejected(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(TicketTypeManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('name', 'General')
            ->set('capacity', '-1')
            ->call('save')
            ->assertHasErrors(['capacity']);
    }

    public function test_availability_end_before_start_is_rejected(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(TicketTypeManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('name', 'Early Bird')
            ->set('available_from', '2026-10-10T09:00')
            ->set('available_until', '2026-10-01T09:00')
            ->call('save')
            ->assertHasErrors(['available_until']);
    }

    public function test_zero_capacity_is_allowed(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(TicketTypeManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('name', 'Waitlist')
            ->set('capacity', '0')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(0, $event->ticketTypes()->first()->capacity);
    }

    public function test_duplicate_name_within_event_is_rejected(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $event->ticketTypes()->create([
            'name' => 'General',
            'price_amount' => 0.00,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Livewire::actingAs($owner)
            ->test(TicketTypeManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('name', 'General')
            ->call('save')
            ->assertHasErrors(['name']);

        $this->assertSame(1, $event->ticketTypes()->count());
    }

    // ─── Update ─────────────────────────────────────────────────────────────

    public function test_owner_can_edit_ticket_type(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $ticketType = TicketType::factory()->for($event)->create(['name' => 'General', 'price_amount' => 0.00]);

        Livewire::actingAs($owner)
            ->test(TicketTypeManager::class, ['event' => $event])
            ->call('beginEdit', $ticketType->id)
            ->set('name', 'Standard')
            ->set('price_amount', '25.00')
            ->set('currency', 'EUR')
            ->call('save')
            ->assertHasNoErrors();

        $ticketType->refresh();
        $this->assertSame('Standard', $ticketType->name);
        $this->assertSame('25.00', (string) $ticketType->price_amount);
        $this->assertSame('EUR', $ticketType->currency);
    }

    // ─── Deactivate / delete ────────────────────────────────────────────────

    public function test_deactivating_ticket_type_preserves_it(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $ticketType = TicketType::factory()->for($event)->create(['name' => 'General']);

        Livewire::actingAs($owner)
            ->test(TicketTypeManager::class, ['event' => $event])
            ->call('setActive', $ticketType->id, false);

        $this->assertSame(1, $event->ticketTypes()->count());
        $this->assertFalse($event->ticketTypes()->first()->is_active);

        Livewire::actingAs($owner)
            ->test(TicketTypeManager::class, ['event' => $event])
            ->call('setActive', $ticketType->id, true);

        $this->assertTrue($event->refresh()->ticketTypes()->first()->is_active);
    }

    public function test_owner_can_delete_ticket_type(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $ticketType = TicketType::factory()->for($event)->create(['name' => 'General']);

        Livewire::actingAs($owner)
            ->test(TicketTypeManager::class, ['event' => $event])
            ->call('delete', $ticketType->id);

        $this->assertDatabaseMissing('ticket_types', ['id' => $ticketType->id]);
    }

    // ─── Reorder ────────────────────────────────────────────────────────────

    public function test_reorder_persists_sort_order(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $first = TicketType::factory()->for($event)->create(['name' => 'First', 'sort_order' => 0]);
        $second = TicketType::factory()->for($event)->create(['name' => 'Second', 'sort_order' => 1]);

        Livewire::actingAs($owner)
            ->test(TicketTypeManager::class, ['event' => $event])
            ->call('moveDown', $first->id);

        $this->assertSame(1, $first->refresh()->sort_order);
        $this->assertSame(0, $second->refresh()->sort_order);
    }

    public function test_reorder_rejects_invalid_set(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        TicketType::factory()->for($event)->create(['name' => 'First', 'sort_order' => 0]);
        TicketType::factory()->for($event)->create(['name' => 'Second', 'sort_order' => 1]);
        $action = new ReorderTicketTypes;

        $this->expectException(ValidationException::class);
        $action->handle($event, $owner, [999]);
    }

    // ─── Authorization ──────────────────────────────────────────────────────

    public function test_assigned_event_manager_can_manage_ticket_types(): void
    {
        [$manager, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::EventManager);
        $event = Event::factory()->for($org)->create();

        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        Livewire::actingAs($manager)
            ->test(TicketTypeManager::class, ['event' => $event])
            ->assertOk()
            ->call('beginCreate')
            ->set('name', 'Manager Ticket')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, $event->ticketTypes()->count());
    }

    public function test_unassigned_staff_cannot_manage_ticket_types(): void
    {
        [$staff] = $this->makeOrgWithMember(OrganizationRole::Staff);
        $event = Event::factory()->create();

        Livewire::actingAs($staff)
            ->test(TicketTypeManager::class, ['event' => $event])
            ->assertStatus(403);
    }

    public function test_other_organization_owner_cannot_manage_ticket_types(): void
    {
        [$otherOwner] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $otherOrg = Organization::factory()->create();
        $event = Event::factory()->for($otherOrg)->create();

        Livewire::actingAs($otherOwner)
            ->test(TicketTypeManager::class, ['event' => $event])
            ->assertStatus(403);

        $this->assertSame(0, $event->ticketTypes()->count());
    }

    // ─── Setup page integration ─────────────────────────────────────────────

    public function test_setup_page_shows_ticket_type_manager(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['status' => 'draft']);

        Livewire::actingAs($owner)
            ->test(EventSetup::class, ['event' => $event])
            ->assertOk()
            ->assertSee('No ticket types yet');
    }
}
