<?php

namespace Tests\Feature\Events;

use App\Actions\Events\ReorderAgendaItems;
use App\Enums\EventStatus;
use App\Enums\OrganizationRole;
use App\Livewire\Events\AgendaManager;
use App\Models\AgendaItem;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AgendaTest extends TestCase
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

    // ─── Create / edit / delete ────────────────────────────────────────────

    public function test_owner_can_create_agenda_item(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['name' => 'Agenda Event']);

        Livewire::actingAs($owner)
            ->test(AgendaManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('title', 'Opening Keynote')
            ->set('start_at', '2026-09-10T09:00')
            ->set('end_at', '2026-09-10T10:00')
            ->set('location', 'Main Stage')
            ->set('speaker_text', 'Jane Doe')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('agenda_items', [
            'event_id' => $event->id,
            'title' => 'Opening Keynote',
            'location' => 'Main Stage',
            'speaker_text' => 'Jane Doe',
            'sort_order' => 1,
        ]);
    }

    public function test_owner_can_edit_agenda_item(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $item = AgendaItem::factory()->for($event)->create(['title' => 'Original Title', 'start_at' => '2026-09-10 09:00:00']);

        Livewire::actingAs($owner)
            ->test(AgendaManager::class, ['event' => $event])
            ->call('beginEdit', $item->id)
            ->assertSet('title', 'Original Title')
            ->set('title', 'Updated Title')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('agenda_items', ['id' => $item->id, 'title' => 'Updated Title']);
    }

    public function test_owner_can_delete_agenda_item(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $item = AgendaItem::factory()->for($event)->create(['title' => 'Remove Me']);

        Livewire::actingAs($owner)
            ->test(AgendaManager::class, ['event' => $event])
            ->call('delete', $item->id);

        $this->assertDatabaseMissing('agenda_items', ['id' => $item->id]);
    }

    // ─── Ordering ──────────────────────────────────────────────────────────

    public function test_agenda_items_list_chronologically_by_default(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        AgendaItem::factory()->for($event)->create(['title' => 'Late Session', 'start_at' => '2026-09-12 09:00:00']);
        AgendaItem::factory()->for($event)->create(['title' => 'Early Session', 'start_at' => '2026-09-10 09:00:00']);
        AgendaItem::factory()->for($event)->create(['title' => 'Mid Session', 'start_at' => '2026-09-11 09:00:00']);

        Livewire::actingAs($owner)
            ->test(AgendaManager::class, ['event' => $event])
            ->assertSeeInOrder(['Early Session', 'Mid Session', 'Late Session']);
    }

    public function test_reorder_persists_sort_order(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['name' => 'Reorder Event']);

        $first = AgendaItem::factory()->for($event)->create(['title' => 'First', 'start_at' => '2026-09-10 09:00:00', 'sort_order' => 0]);
        $second = AgendaItem::factory()->for($event)->create(['title' => 'Second', 'start_at' => '2026-09-10 09:00:00', 'sort_order' => 1]);

        Livewire::actingAs($owner)
            ->test(AgendaManager::class, ['event' => $event])
            ->call('moveDown', $first->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('agenda_items', ['id' => $first->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('agenda_items', ['id' => $second->id, 'sort_order' => 0]);
    }

    public function test_reorder_rejects_invalid_item_set(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $item = AgendaItem::factory()->for($event)->create();

        $this->expectException(ValidationException::class);

        app(ReorderAgendaItems::class)->handle($event, $owner, [$item->id, 999999]);
    }

    public function test_empty_agenda_shows_empty_state(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(AgendaManager::class, ['event' => $event])
            ->assertSee('No agenda items yet')
            ->assertSee('Add Agenda Item');
    }

    // ─── Validation ────────────────────────────────────────────────────────

    public function test_agenda_item_requires_title_and_start(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(AgendaManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('title', '')
            ->set('start_at', '')
            ->call('save')
            ->assertHasErrors(['title', 'start_at']);
    }

    public function test_agenda_end_must_not_precede_start(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(AgendaManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('title', 'Bad Session')
            ->set('start_at', '2026-09-10T10:00')
            ->set('end_at', '2026-09-10T09:00')
            ->call('save')
            ->assertHasErrors('end_at');

        $this->assertDatabaseCount('agenda_items', 0);
    }

    public function test_published_event_cannot_have_agenda_managed(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['status' => EventStatus::Published]);

        Livewire::actingAs($owner)
            ->test(AgendaManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('title', 'Too Late')
            ->set('start_at', '2026-09-10T09:00')
            ->call('save')
            ->assertHasErrors('status');

        $this->assertDatabaseCount('agenda_items', 0);
    }

    // ─── Authorization ─────────────────────────────────────────────────────

    public function test_assigned_event_manager_can_manage_agenda(): void
    {
        [$manager, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::EventManager);
        $event = Event::factory()->for($org)->create();

        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        Livewire::actingAs($manager)
            ->test(AgendaManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('title', 'Managed Session')
            ->set('start_at', '2026-09-10T09:00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('agenda_items', ['event_id' => $event->id, 'title' => 'Managed Session']);
    }

    public function test_unassigned_staff_cannot_access_agenda_manager(): void
    {
        [$staff, $org] = $this->makeOrgWithMember(OrganizationRole::Staff);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($staff)
            ->test(AgendaManager::class, ['event' => $event])
            ->assertStatus(403);
    }

    public function test_cross_org_agenda_manager_is_blocked(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $otherOrg = Organization::factory()->create();
        $otherEvent = Event::factory()->for($otherOrg)->create();

        Livewire::actingAs($owner)
            ->test(AgendaManager::class, ['event' => $otherEvent])
            ->assertStatus(403);
    }

    // ─── Setup page integration ────────────────────────────────────────────

    public function test_owner_event_setup_shows_agenda_manager(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['name' => 'Setup With Agenda']);

        $this->actingAs($owner)
            ->get(route('app.events.setup', $event))
            ->assertOk()
            ->assertSee('Setup With Agenda')
            ->assertSee('Add Agenda Item');
    }
}
