<?php

namespace Tests\Feature\Organizations;

use App\Enums\OrganizationRole;
use App\Livewire\Organizations\SettingsForm;
use App\Models\Organization;
use App\Models\User;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithOrganizations;
use Tests\TestCase;

class SettingsAuthorizationTest extends TestCase
{
    use InteractsWithOrganizations;
    use RefreshDatabase;

    #[DataProvider('rolesAllowedToManageSettings')]
    public function test_authorized_roles_can_view_settings_page(OrganizationRole $role): void
    {
        $user = User::factory()->create();
        $organization = $this->createOrganizationForUser($user, role: $role);

        $this->actingAs($user)
            ->withSession([OrganizationContext::SESSION_KEY => $organization->id])
            ->get(route('app.settings.index'))
            ->assertOk()
            ->assertSee('Organization Settings', false);
    }

    /**
     * @return array<string, array{OrganizationRole}>
     */
    public static function rolesAllowedToManageSettings(): array
    {
        return [
            'owner' => [OrganizationRole::Owner],
            'admin' => [OrganizationRole::Admin],
        ];
    }

    #[DataProvider('rolesDeniedFromManagingSettings')]
    public function test_unauthorized_roles_are_forbidden_from_settings_page(OrganizationRole $role): void
    {
        $user = User::factory()->create();
        $organization = $this->createOrganizationForUser($user, role: $role);

        $this->actingAs($user)
            ->withSession([OrganizationContext::SESSION_KEY => $organization->id])
            ->get(route('app.settings.index'))
            ->assertForbidden();
    }

    /**
     * @return array<string, array{OrganizationRole}>
     */
    public static function rolesDeniedFromManagingSettings(): array
    {
        return [
            'event manager' => [OrganizationRole::EventManager],
            'staff' => [OrganizationRole::Staff],
            'viewer' => [OrganizationRole::Viewer],
        ];
    }

    public function test_staff_cannot_mutate_settings_via_page(): void
    {
        $staff = User::factory()->create();
        $organization = $this->createOrganizationForUser($staff, ['name' => 'Original Name'], OrganizationRole::Staff);

        session([OrganizationContext::SESSION_KEY => $organization->id]);

        Livewire::actingAs($staff)
            ->test(SettingsForm::class)
            ->assertStatus(403);

        $this->assertSame('Original Name', $organization->refresh()->name);
    }

    public function test_owner_saves_settings_from_page(): void
    {
        $owner = User::factory()->create();
        $organization = $this->createOrganizationForUser($owner, ['name' => 'Original Name']);

        session([OrganizationContext::SESSION_KEY => $organization->id]);

        Livewire::actingAs($owner)
            ->test(SettingsForm::class)
            ->set('name', 'Acme Events')
            ->set('timezone', 'Europe/London')
            ->set('locale', 'en')
            ->set('default_currency', 'GBP')
            ->call('save')
            ->assertHasNoErrors();

        $organization->refresh();

        $this->assertSame('Acme Events', $organization->name);
        $this->assertSame('Europe/London', $organization->timezone);
        $this->assertSame('GBP', $organization->default_currency);
    }

    public function test_page_rejects_invalid_timezone(): void
    {
        $owner = User::factory()->create();
        $organization = $this->createOrganizationForUser($owner, ['name' => 'Original Name']);

        session([OrganizationContext::SESSION_KEY => $organization->id]);

        Livewire::actingAs($owner)
            ->test(SettingsForm::class)
            ->set('timezone', 'Mars/Olympus')
            ->call('save')
            ->assertHasErrors('timezone');

        $this->assertSame('UTC', $organization->refresh()->timezone);
    }

    public function test_settings_do_not_leak_across_organizations(): void
    {
        $owner = User::factory()->create();
        $organization = $this->createOrganizationForUser($owner, ['name' => 'Acme Events']);
        Organization::factory()->create(['name' => 'Private Rival']);

        $this->actingAs($owner)
            ->withSession([OrganizationContext::SESSION_KEY => $organization->id])
            ->get(route('app.settings.index'))
            ->assertOk()
            ->assertSee('Acme Events', false)
            ->assertDontSee('Private Rival', false);
    }
}
