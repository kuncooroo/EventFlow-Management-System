<?php

namespace Tests\Feature\Events;

use App\Enums\EventStatus;
use App\Enums\OrganizationRole;
use App\Livewire\Events\RegistrationSettingsForm;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationSettingsTest extends TestCase
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

    // ─── Persistence ────────────────────────────────────────────────────────

    public function test_owner_can_enable_registration_with_window_capacity_and_fields(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['name' => 'Registration Event']);

        Livewire::actingAs($owner)
            ->test(RegistrationSettingsForm::class, ['event' => $event])
            ->set('registration_enabled', true)
            ->set('registration_starts_at', '2026-10-01T09:00')
            ->set('registration_ends_at', '2026-10-02T17:00')
            ->set('capacity', '500')
            ->set('require_phone', true)
            ->set('require_organization', true)
            ->call('save')
            ->assertHasNoErrors();

        $event->refresh();

        $this->assertTrue($event->registration_enabled);
        $this->assertSame('2026-10-01 09:00:00', $event->registration_starts_at?->toDateTimeString());
        $this->assertSame('2026-10-02 17:00:00', $event->registration_ends_at?->toDateTimeString());
        $this->assertSame(500, $event->capacity);
        $this->assertTrue($event->require_phone);
        $this->assertTrue($event->require_organization);
    }

    public function test_owner_can_disable_registration(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['registration_enabled' => true]);

        Livewire::actingAs($owner)
            ->test(RegistrationSettingsForm::class, ['event' => $event])
            ->assertSet('registration_enabled', true)
            ->set('registration_enabled', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($event->refresh()->registration_enabled);
    }

    public function test_blank_window_means_no_limit(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(RegistrationSettingsForm::class, ['event' => $event])
            ->set('registration_enabled', true)
            ->set('capacity', '')
            ->call('save')
            ->assertHasNoErrors();

        $event->refresh();

        $this->assertNull($event->registration_starts_at);
        $this->assertNull($event->registration_ends_at);
        $this->assertNull($event->capacity);
    }

    // ─── Validation ─────────────────────────────────────────────────────────

    public function test_registration_end_before_start_is_rejected(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['registration_enabled' => false]);

        Livewire::actingAs($owner)
            ->test(RegistrationSettingsForm::class, ['event' => $event])
            ->set('registration_enabled', true)
            ->set('registration_starts_at', '2026-10-02T17:00')
            ->set('registration_ends_at', '2026-10-01T09:00')
            ->call('save')
            ->assertHasErrors('registration_ends_at');

        $this->assertFalse($event->refresh()->registration_enabled);
    }

    public function test_negative_capacity_is_rejected(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(RegistrationSettingsForm::class, ['event' => $event])
            ->set('capacity', '-5')
            ->call('save')
            ->assertHasErrors('capacity');

        $this->assertNull($event->refresh()->capacity);
    }

    // ─── Status edge case ───────────────────────────────────────────────────

    public function test_registration_settings_can_be_updated_on_published_event(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create([
            'status' => EventStatus::Published,
            'registration_enabled' => true,
        ]);

        Livewire::actingAs($owner)
            ->test(RegistrationSettingsForm::class, ['event' => $event])
            ->set('registration_enabled', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($event->refresh()->registration_enabled);
    }

    // ─── Availability indicator ─────────────────────────────────────────────

    public function test_availability_indicator_shows_disabled_state(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['registration_enabled' => false]);

        Livewire::actingAs($owner)
            ->test(RegistrationSettingsForm::class, ['event' => $event])
            ->assertSee('Registration is disabled for this event.');
    }

    public function test_availability_indicator_shows_configured_for_draft(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['registration_enabled' => true]);

        Livewire::actingAs($owner)
            ->test(RegistrationSettingsForm::class, ['event' => $event])
            ->assertSee('Registration is configured — Draft events are not public yet.');
    }

    // ─── Authorization ─────────────────────────────────────────────────────

    public function test_assigned_event_manager_can_update_registration_settings(): void
    {
        [$manager, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::EventManager);
        $event = Event::factory()->for($org)->create();

        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        Livewire::actingAs($manager)
            ->test(RegistrationSettingsForm::class, ['event' => $event])
            ->set('registration_enabled', true)
            ->set('require_phone', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($event->refresh()->registration_enabled);
        $this->assertTrue($event->refresh()->require_phone);
    }

    public function test_unassigned_staff_cannot_access_registration_settings(): void
    {
        [$staff, $org] = $this->makeOrgWithMember(OrganizationRole::Staff);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($staff)
            ->test(RegistrationSettingsForm::class, ['event' => $event])
            ->assertStatus(403);
    }

    public function test_cross_org_registration_settings_are_blocked(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $otherOrg = Organization::factory()->create();
        $otherEvent = Event::factory()->for($otherOrg)->create();

        Livewire::actingAs($owner)
            ->test(RegistrationSettingsForm::class, ['event' => $otherEvent])
            ->assertStatus(403);
    }

    // ─── Setup page integration ─────────────────────────────────────────────

    public function test_owner_event_setup_shows_registration_panel(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['name' => 'Setup With Registration']);

        $this->actingAs($owner)
            ->get(route('app.events.setup', $event))
            ->assertOk()
            ->assertSee('Setup With Registration')
            ->assertSee('Enable registration');
    }
}
