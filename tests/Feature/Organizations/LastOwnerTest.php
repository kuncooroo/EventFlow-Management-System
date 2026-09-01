<?php

namespace Tests\Feature\Organizations;

use App\Actions\Organizations\ChangeMemberRole;
use App\Actions\Organizations\RemoveMember;
use App\Enums\OrganizationRole;
use App\Livewire\Organizations\MemberIndex;
use App\Models\User;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithOrganizations;
use Tests\TestCase;

class LastOwnerTest extends TestCase
{
    use InteractsWithOrganizations;
    use RefreshDatabase;

    public function test_last_owner_cannot_be_demoted(): void
    {
        $owner = User::factory()->create();
        $organization = $this->createOrganizationForUser($owner, role: OrganizationRole::Owner);

        $membership = $owner->membershipIn($organization);
        $this->assertNotNull($membership);

        $this->expectException(ValidationException::class);

        app(ChangeMemberRole::class)->handle(
            $membership,
            OrganizationRole::Admin,
            $owner,
            $organization,
        );
    }

    public function test_last_owner_cannot_be_removed(): void
    {
        $owner = User::factory()->create();
        $organization = $this->createOrganizationForUser($owner, role: OrganizationRole::Owner);

        $membership = $owner->membershipIn($organization);
        $this->assertNotNull($membership);

        $this->expectException(ValidationException::class);

        app(RemoveMember::class)->handle($membership, $owner, $organization);
    }

    public function test_owner_can_be_demoted_when_another_owner_exists(): void
    {
        $primaryOwner = User::factory()->create();
        $organization = $this->createOrganizationForUser($primaryOwner, role: OrganizationRole::Owner);
        $secondaryOwner = User::factory()->create();
        $this->addMemberToOrganization($organization, $secondaryOwner, OrganizationRole::Owner);

        $membership = $primaryOwner->membershipIn($organization);
        $this->assertNotNull($membership);

        app(ChangeMemberRole::class)->handle(
            $membership,
            OrganizationRole::Admin,
            $secondaryOwner,
            $organization,
        );

        $membership->refresh();

        $this->assertSame(OrganizationRole::Admin, $membership->role);
    }

    public function test_owner_can_be_removed_when_another_owner_exists(): void
    {
        $primaryOwner = User::factory()->create();
        $organization = $this->createOrganizationForUser($primaryOwner, role: OrganizationRole::Owner);
        $secondaryOwner = User::factory()->create();
        $this->addMemberToOrganization($organization, $secondaryOwner, OrganizationRole::Owner);

        $membership = $primaryOwner->membershipIn($organization);
        $this->assertNotNull($membership);

        app(RemoveMember::class)->handle($membership, $secondaryOwner, $organization);

        $membership->refresh();

        $this->assertNotNull($membership->removed_at);
    }

    public function test_second_owner_demotion_is_blocked_after_first_owner_is_demoted(): void
    {
        $firstOwner = User::factory()->create();
        $organization = $this->createOrganizationForUser($firstOwner, role: OrganizationRole::Owner);
        $secondOwner = User::factory()->create();
        $secondMembership = $this->addMemberToOrganization($organization, $secondOwner, OrganizationRole::Owner);

        app(ChangeMemberRole::class)->handle(
            $firstOwner->membershipIn($organization),
            OrganizationRole::Admin,
            $secondOwner,
            $organization,
        );

        Livewire::actingAs($secondOwner)
            ->withSession([OrganizationContext::SESSION_KEY => $organization->id])
            ->test(MemberIndex::class)
            ->call('changeRole', $secondMembership->id, OrganizationRole::Admin->value)
            ->assertHasErrors(['role']);

        $secondMembership->refresh();

        $this->assertSame(OrganizationRole::Owner, $secondMembership->role);
    }

    public function test_removed_member_loses_organization_access(): void
    {
        $owner = User::factory()->create();
        $organization = $this->createOrganizationForUser($owner, role: OrganizationRole::Owner);
        $member = User::factory()->create();
        $membership = $this->addMemberToOrganization($organization, $member, OrganizationRole::Staff);

        Livewire::actingAs($owner)
            ->withSession([OrganizationContext::SESSION_KEY => $organization->id])
            ->test(MemberIndex::class)
            ->call('removeMember', $membership->id)
            ->assertHasNoErrors();

        $membership->refresh();
        $this->assertNotNull($membership->removed_at);

        $this->actingAs($member)
            ->withSession([OrganizationContext::SESSION_KEY => $organization->id])
            ->get(route('app.dashboard'))
            ->assertRedirect(route('app.organizations.create'));
    }
}
