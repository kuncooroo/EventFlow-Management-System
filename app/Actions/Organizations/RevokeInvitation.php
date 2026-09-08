<?php

namespace App\Actions\Organizations;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RevokeInvitation
{
    public function handle(
        OrganizationInvitation $invitation,
        User $actor,
        Organization $organization,
    ): OrganizationInvitation {
        $this->assertInvitationInOrganization($invitation, $organization);
        $this->assertActorCanManage($actor, $organization);

        if (! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'invitation' => __('Only pending invitations can be revoked.'),
            ]);
        }

        $invitation->update([
            'revoked_at' => now(),
        ]);

        return $invitation->refresh();
    }

    private function assertInvitationInOrganization(
        OrganizationInvitation $invitation,
        Organization $organization,
    ): void {
        if ($invitation->organization_id !== $organization->id) {
            throw ValidationException::withMessages([
                'invitation' => __('Invitation not found in this organization.'),
            ]);
        }
    }

    private function assertActorCanManage(User $actor, Organization $organization): void
    {
        $actorMembership = $actor->membershipIn($organization);

        if ($actorMembership === null || ! $actorMembership->role->canManageMembers()) {
            throw ValidationException::withMessages([
                'invitation' => __('You are not authorized to revoke invitations.'),
            ]);
        }
    }
}
