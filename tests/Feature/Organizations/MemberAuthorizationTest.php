<?php

namespace Tests\Feature\Organizations;

use App\Enums\OrganizationRole;
use App\Livewire\Organizations\MemberIndex;
use App\Models\Organization;
use App\Models\User;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithOrganizations;
use Tests\TestCase;

class MemberAuthorizationTest extends TestCase
{
    use InteractsWithOrganizations;
    use RefreshDatabase;

    #[DataProvider('rolesAllowedToViewMembers')]
    public function test_authorized_roles_can_view_members_page(OrganizationRole $role): void
    {
        $user = User::factory()->create();
        $organization = $this->createOrganizationForUser($user, role: $role);

        $this->actingAs($user)
            ->withSession([OrganizationContext::SESSION_KEY => $organization->id])
            ->get(route('app.members.index'))
            ->assertOk()
            ->assertSee('Members', false);
    }

    /**
     * @return array<string, array{OrganizationRole}>
     */
    public static function rolesAllowedToViewMembers(): array
    {
        return [
            'owner' => [OrganizationRole::Owner],
            'admin' => [OrganizationRole::Admin],
            'event manager' => [OrganizationRole::EventManager],
        ];
    }

    #[DataProvider('rolesDeniedFromViewingMembers')]
    public function test_unauthorized_roles_cannot_view_members_page(OrganizationRole $role): void
    {
        $user = User::factory()->create();
        $organization = $this->createOrganizationForUser($user, role: $role);

        $this->actingAs($user)
            ->withSession([OrganizationContext::SESSION_KEY => $organization->id])
            ->get(route('app.members.index'))
            ->assertForbidden();
    }

    /**
     * @return array<string, array{OrganizationRole}>
     */
    public static function rolesDeniedFromViewingMembers(): array
    {
        return [
            'staff' => [OrganizationRole::Staff],
            'viewer' => [OrganizationRole::Viewer],
        ];
    }

    #[DataProvider('rolesAllowedToManageMembers')]
    public function test_authorized_roles_can_change_member_roles(OrganizationRole $role): void
    {
        $actor = User::factory()->create();
        $organization = $this->createOrganizationForUser($actor, role: $role);
        $member = $this->addMemberToOrganization($organization, role: OrganizationRole::Viewer);

        Livewire::actingAs($actor)
            ->withSession([OrganizationContext::SESSION_KEY => $organization->id])
            ->test(MemberIndex::class)
            ->call('changeRole', $member->id, OrganizationRole::Staff->value)
            ->assertHasNoErrors();

        $member->refresh();

        $this->assertSame(OrganizationRole::Staff, $member->role);
    }

    /**
     * @return array<string, array{OrganizationRole}>
     */
    public static function rolesAllowedToManageMembers(): array
    {
        return [
            'owner' => [OrganizationRole::Owner],
            'admin' => [OrganizationRole::Admin],
        ];
    }

    #[DataProvider('rolesDeniedFromManagingMembers')]
    public function test_unauthorized_roles_cannot_change_member_roles(OrganizationRole $role): void
    {
        $actor = User::factory()->create();
        $organization = $this->createOrganizationForUser($actor, role: $role);
        $member = $this->addMemberToOrganization($organization, role: OrganizationRole::Viewer);

        Livewire::actingAs($actor)
            ->withSession([OrganizationContext::SESSION_KEY => $organization->id])
            ->test(MemberIndex::class)
            ->call('changeRole', $member->id, OrganizationRole::Staff->value)
            ->assertForbidden();

        $member->refresh();

        $this->assertSame(OrganizationRole::Viewer, $member->role);
    }

    /**
     * @return array<string, array{OrganizationRole}>
     */
    public static function rolesDeniedFromManagingMembers(): array
    {
        return [
            'event manager' => [OrganizationRole::EventManager],
            'staff' => [OrganizationRole::Staff],
            'viewer' => [OrganizationRole::Viewer],
        ];
    }

    public function test_admin_cannot_manage_member_in_foreign_organization(): void
    {
        $actor = User::factory()->create();
        $organization = $this->createOrganizationForUser($actor, role: OrganizationRole::Admin);

        $foreignOrganization = Organization::factory()->create(['name' => 'Foreign Org']);
        $foreignMember = $this->addMemberToOrganization($foreignOrganization, role: OrganizationRole::Viewer);

        Livewire::actingAs($actor)
            ->withSession([OrganizationContext::SESSION_KEY => $organization->id])
            ->test(MemberIndex::class)
            ->call('changeRole', $foreignMember->id, OrganizationRole::Staff->value)
            ->assertNotFound();

        $foreignMember->refresh();

        $this->assertSame(OrganizationRole::Viewer, $foreignMember->role);
    }

    public function test_event_manager_sees_read_only_member_list(): void
    {
        $actor = User::factory()->create();
        $organization = $this->createOrganizationForUser($actor, role: OrganizationRole::EventManager);
        $member = $this->addMemberToOrganization(
            $organization,
            User::factory()->create(['name' => 'Read Only Member']),
            OrganizationRole::Staff,
        );

        Livewire::actingAs($actor)
            ->withSession([OrganizationContext::SESSION_KEY => $organization->id])
            ->test(MemberIndex::class)
            ->assertSee('Read Only Member', false)
            ->assertSee('Staff', false)
            ->assertDontSee('wire:confirm', false);
    }
}
