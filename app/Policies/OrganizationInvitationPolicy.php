<?php

namespace App\Policies;

use App\Models\OrganizationInvitation;
use App\Models\User;

class OrganizationInvitationPolicy
{
    public function create(User $user, OrganizationInvitation $invitation): bool
    {
        $actorMembership = $user->membershipIn($invitation->organization);

        return $actorMembership !== null && $actorMembership->role->canManageMembers();
    }

    public function delete(User $user, OrganizationInvitation $invitation): bool
    {
        if (! $invitation->isPending()) {
            return false;
        }

        $actorMembership = $user->membershipIn($invitation->organization);

        return $actorMembership !== null && $actorMembership->role->canManageMembers();
    }

    public function accept(User $user, OrganizationInvitation $invitation): bool
    {
        return $invitation->isPending() && strcasecmp($user->email, $invitation->email) === 0;
    }
}
