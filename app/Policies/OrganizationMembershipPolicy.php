<?php

namespace App\Policies;

use App\Models\OrganizationMembership;
use App\Models\User;

class OrganizationMembershipPolicy
{
    public function update(User $user, OrganizationMembership $membership): bool
    {
        if (! $membership->isActive()) {
            return false;
        }

        $organization = $membership->organization;

        if ($organization === null) {
            return false;
        }

        $actorMembership = $user->membershipIn($organization);

        return $actorMembership !== null && $actorMembership->role->canManageMembers();
    }

    public function delete(User $user, OrganizationMembership $membership): bool
    {
        return $this->update($user, $membership);
    }
}
