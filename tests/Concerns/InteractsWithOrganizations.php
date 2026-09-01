<?php

namespace Tests\Concerns;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;

trait InteractsWithOrganizations
{
    protected function createOrganizationForUser(
        User $user,
        array $organizationAttributes = [],
        OrganizationRole $role = OrganizationRole::Owner,
    ): Organization {
        $organization = Organization::factory()->create($organizationAttributes);

        OrganizationMembership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => now(),
            'removed_at' => null,
        ]);

        return $organization;
    }

    protected function addMemberToOrganization(
        Organization $organization,
        ?User $user = null,
        OrganizationRole $role = OrganizationRole::Viewer,
    ): OrganizationMembership {
        $user ??= User::factory()->create();

        return OrganizationMembership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => now(),
            'removed_at' => null,
        ]);
    }
}
