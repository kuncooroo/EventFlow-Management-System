<?php

namespace Tests\Feature\Events;

use App\Enums\OrganizationRole;
use App\Livewire\Events\ReminderSettingsForm;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReminderSettingsTest extends TestCase
{
    use RefreshDatabase;

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

    // ─── Persistence ────────────────────────────────────────────────────────

    public function test_owner_can_enable_reminder_with_hour_offset(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['start_at' => now()->addDays(2)]);

        Livewire::actingAs($owner)
            ->test(ReminderSettingsForm::class, ['event' => $event])
            ->set('reminder_enabled', true)
            ->set('reminder_hours_before', '24')
            ->call('save')
            ->assertHasNoErrors();

        $event->refresh();

        $this->assertTrue($event->reminder_enabled);
        $this->assertSame(24, $event->reminder_hours_before);
    }

    public function test_owner_can_disable_reminder(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create([
            'start_at' => now()->addDays(2),
            'reminder_enabled' => true,
            'reminder_hours_before' => 48,
        ]);

        Livewire::actingAs($owner)
            ->test(ReminderSettingsForm::class, ['event' => $event])
            ->set('reminder_enabled', false)
            ->set('reminder_hours_before', '')
            ->call('save')
            ->assertHasNoErrors();

        $event->refresh();

        $this->assertFalse($event->reminder_enabled);
        $this->assertNull($event->reminder_hours_before);
    }

    // ─── Validation ─────────────────────────────────────────────────────────

    public function test_offset_is_required_when_reminder_is_enabled(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['start_at' => now()->addDays(2)]);

        Livewire::actingAs($owner)
            ->test(ReminderSettingsForm::class, ['event' => $event])
            ->set('reminder_enabled', true)
            ->set('reminder_hours_before', '')
            ->call('save')
            ->assertHasErrors('reminder_hours_before');

        $this->assertFalse($event->refresh()->reminder_enabled);
    }

    public function test_offset_below_one_hour_is_rejected(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['start_at' => now()->addDays(2)]);

        Livewire::actingAs($owner)
            ->test(ReminderSettingsForm::class, ['event' => $event])
            ->set('reminder_enabled', true)
            ->set('reminder_hours_before', '0')
            ->call('save')
            ->assertHasErrors('reminder_hours_before');

        $this->assertFalse($event->refresh()->reminder_enabled);
    }

    public function test_offset_above_720_hours_is_rejected(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['start_at' => now()->addDays(40)]);

        Livewire::actingAs($owner)
            ->test(ReminderSettingsForm::class, ['event' => $event])
            ->set('reminder_enabled', true)
            ->set('reminder_hours_before', '721')
            ->call('save')
            ->assertHasErrors('reminder_hours_before');

        $this->assertFalse($event->refresh()->reminder_enabled);
    }

    // ─── Setup page preview ─────────────────────────────────────────────────

    public function test_setup_page_shows_reminder_due_preview(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create([
            'start_at' => now()->addDays(2),
            'reminder_enabled' => true,
            'reminder_hours_before' => 24,
        ]);

        Livewire::actingAs($owner)
            ->test(ReminderSettingsForm::class, ['event' => $event])
            ->set('reminder_enabled', true)
            ->set('reminder_hours_before', '24')
            ->assertSee('hours');
    }

    // ─── Authorization ─────────────────────────────────────────────────────

    public function test_assigned_event_manager_can_update_reminder_settings(): void
    {
        [$manager, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::EventManager);
        $event = Event::factory()->for($org)->create(['start_at' => now()->addDays(2)]);

        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        Livewire::actingAs($manager)
            ->test(ReminderSettingsForm::class, ['event' => $event])
            ->set('reminder_enabled', true)
            ->set('reminder_hours_before', '24')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($event->refresh()->reminder_enabled);
    }

    public function test_unassigned_staff_cannot_access_reminder_settings(): void
    {
        [$staff, $org] = $this->makeOrgWithMember(OrganizationRole::Staff);
        $event = Event::factory()->for($org)->create(['start_at' => now()->addDays(2)]);

        Livewire::actingAs($staff)
            ->test(ReminderSettingsForm::class, ['event' => $event])
            ->assertStatus(403);
    }

    public function test_cross_org_reminder_settings_are_blocked(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $otherOrg = Organization::factory()->create();
        $otherEvent = Event::factory()->for($otherOrg)->create(['start_at' => now()->addDays(2)]);

        Livewire::actingAs($owner)
            ->test(ReminderSettingsForm::class, ['event' => $otherEvent])
            ->assertStatus(403);
    }

    // ─── Setup page integration ─────────────────────────────────────────────

    public function test_owner_event_setup_shows_reminder_panel(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['name' => 'Setup With Reminder']);

        $this->actingAs($owner)
            ->get(route('app.events.setup', $event))
            ->assertOk()
            ->assertSee('Setup With Reminder')
            ->assertSee('Enable event reminder');
    }
}
