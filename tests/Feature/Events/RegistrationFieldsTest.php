<?php

namespace Tests\Feature\Events;

use App\Actions\Events\ReorderRegistrationFields;
use App\Enums\OrganizationRole;
use App\Enums\RegistrationFieldType;
use App\Livewire\Events\RegistrationFieldManager;
use App\Models\Event;
use App\Models\Organization;
use App\Models\RegistrationField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationFieldsTest extends TestCase
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

    public function test_owner_can_create_text_field(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['name' => 'Fields Event']);

        Livewire::actingAs($owner)
            ->test(RegistrationFieldManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('label', 'Institution')
            ->set('field_type', RegistrationFieldType::Text->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('registration_fields', [
            'event_id' => $event->id,
            'label' => 'Institution',
            'field_type' => RegistrationFieldType::Text->value,
            'is_required' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $this->assertNotNull($event->registrationFields()->first()->field_key);
    }

    public function test_select_field_requires_and_stores_options(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(RegistrationFieldManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('label', 'T-Shirt Size')
            ->set('field_type', RegistrationFieldType::Select->value)
            ->set('options_text', "S\nM\nL")
            ->call('save')
            ->assertHasNoErrors();

        $field = $event->registrationFields()->first();
        $this->assertSame(['S', 'M', 'L'], $field->options());
    }

    public function test_choice_field_rejects_missing_options(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(RegistrationFieldManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('label', 'Bad Select')
            ->set('field_type', RegistrationFieldType::Select->value)
            ->set('options_text', '')
            ->call('save')
            ->assertHasErrors('options_text');

        $this->assertDatabaseCount('registration_fields', 0);
    }

    public function test_required_flag_is_persisted(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(RegistrationFieldManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('label', 'Required Question')
            ->set('field_type', RegistrationFieldType::Date->value)
            ->set('is_required', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('registration_fields', [
            'event_id' => $event->id,
            'label' => 'Required Question',
            'is_required' => true,
        ]);
    }

    // ─── Validation ─────────────────────────────────────────────────────────

    public function test_label_is_required(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(RegistrationFieldManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('label', '')
            ->set('field_type', RegistrationFieldType::Text->value)
            ->call('save')
            ->assertHasErrors('label');

        $this->assertDatabaseCount('registration_fields', 0);
    }

    public function test_unsupported_field_type_is_rejected(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(RegistrationFieldManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('label', 'Weird')
            ->set('field_type', 'magic')
            ->call('save')
            ->assertHasErrors('field_type');

        $this->assertDatabaseCount('registration_fields', 0);
    }

    // ─── Update / deactivate ───────────────────────────────────────────────

    public function test_owner_can_edit_field(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $field = RegistrationField::factory()->for($event)->create(['label' => 'Old Label', 'field_type' => RegistrationFieldType::Text]);

        Livewire::actingAs($owner)
            ->test(RegistrationFieldManager::class, ['event' => $event])
            ->call('beginEdit', $field->id)
            ->assertSet('label', 'Old Label')
            ->set('label', 'New Label')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('registration_fields', ['id' => $field->id, 'label' => 'New Label']);
    }

    public function test_deactivating_preserves_field_record(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $field = RegistrationField::factory()->for($event)->active()->create();

        Livewire::actingAs($owner)
            ->test(RegistrationFieldManager::class, ['event' => $event])
            ->call('setActive', $field->id, false);

        $this->assertDatabaseHas('registration_fields', ['id' => $field->id, 'is_active' => false]);
        $this->assertDatabaseCount('registration_fields', 1);
    }

    public function test_reactivating_field(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $field = RegistrationField::factory()->for($event)->inactive()->create();

        Livewire::actingAs($owner)
            ->test(RegistrationFieldManager::class, ['event' => $event])
            ->call('setActive', $field->id, true);

        $this->assertDatabaseHas('registration_fields', ['id' => $field->id, 'is_active' => true]);
    }

    public function test_owner_can_delete_field(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $field = RegistrationField::factory()->for($event)->create();

        Livewire::actingAs($owner)
            ->test(RegistrationFieldManager::class, ['event' => $event])
            ->call('delete', $field->id);

        $this->assertDatabaseMissing('registration_fields', ['id' => $field->id]);
    }

    // ─── Reorder ────────────────────────────────────────────────────────────

    public function test_reorder_persists_sort_order(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        $first = RegistrationField::factory()->for($event)->create(['label' => 'First', 'sort_order' => 0]);
        $second = RegistrationField::factory()->for($event)->create(['label' => 'Second', 'sort_order' => 1]);

        Livewire::actingAs($owner)
            ->test(RegistrationFieldManager::class, ['event' => $event])
            ->call('moveDown', $first->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('registration_fields', ['id' => $first->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('registration_fields', ['id' => $second->id, 'sort_order' => 0]);
    }

    public function test_reorder_rejects_invalid_field_set(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();
        $field = RegistrationField::factory()->for($event)->create();

        $this->expectException(ValidationException::class);

        app(ReorderRegistrationFields::class)->handle($event, $owner, [$field->id, 999999]);
    }

    public function test_field_key_is_unique_per_event(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(RegistrationFieldManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('label', 'Institution')
            ->set('field_type', RegistrationFieldType::Text->value)
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($owner)
            ->test(RegistrationFieldManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('label', 'Institution')
            ->set('field_type', RegistrationFieldType::Text->value)
            ->call('save')
            ->assertHasNoErrors();

        $keys = $event->registrationFields()->pluck('field_key');
        $this->assertSame($keys->count(), $keys->unique()->count());
    }

    // ─── Authorization ─────────────────────────────────────────────────────

    public function test_assigned_event_manager_can_manage_fields(): void
    {
        [$manager, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::EventManager);
        $event = Event::factory()->for($org)->create();

        $event->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        Livewire::actingAs($manager)
            ->test(RegistrationFieldManager::class, ['event' => $event])
            ->call('beginCreate')
            ->set('label', 'Managed Field')
            ->set('field_type', RegistrationFieldType::Text->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('registration_fields', ['event_id' => $event->id, 'label' => 'Managed Field']);
    }

    public function test_unassigned_staff_cannot_access_field_manager(): void
    {
        [$staff, $org] = $this->makeOrgWithMember(OrganizationRole::Staff);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($staff)
            ->test(RegistrationFieldManager::class, ['event' => $event])
            ->assertStatus(403);
    }

    public function test_cross_org_field_manager_is_blocked(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $otherOrg = Organization::factory()->create();
        $otherEvent = Event::factory()->for($otherOrg)->create();

        Livewire::actingAs($owner)
            ->test(RegistrationFieldManager::class, ['event' => $otherEvent])
            ->assertStatus(403);
    }

    // ─── Setup page integration ────────────────────────────────────────────

    public function test_owner_event_setup_shows_custom_fields_panel(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['name' => 'Setup With Fields']);

        $this->actingAs($owner)
            ->get(route('app.events.setup', $event))
            ->assertOk()
            ->assertSee('Setup With Fields')
            ->assertSee('No custom fields yet');
    }
}
