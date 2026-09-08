<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Support\Demo\DemoMode;

class OrganizationPolicy
{
    public function create(User $user): bool
    {
        if (DemoMode::enabled()) {
            return false;
        }

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

    public function manageSettings(User $user, Organization $organization): bool
    {
        $membership = $user->membershipIn($organization);

        return $membership !== null && $membership->role->canManageSettings();
    }
}
