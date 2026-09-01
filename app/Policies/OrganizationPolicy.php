<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function switch(User $user, Organization $organization): bool
    {
        return $user->activeOrganizationMemberships()
            ->where('organization_id', $organization->id)
            ->exists();
    }

    public function viewMembers(User $user, Organization $organization): bool
    {
        $membership = $user->membershipIn($organization);

        return $membership !== null && $membership->role->canViewMembers();
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        $membership = $user->membershipIn($organization);

        return $membership !== null && $membership->role->canManageMembers();
    }
}
