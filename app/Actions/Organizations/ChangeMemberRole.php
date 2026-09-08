<?php

namespace App\Actions\Organizations;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Support\Demo\DemoGate;
use App\Support\Organization\OrganizationOwnerGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeMemberRole
{
    public function __construct(
        private readonly OrganizationOwnerGuard $ownerGuard,
    ) {}

    public function handle(
        OrganizationMembership $membership,
        OrganizationRole $newRole,
        User $actor,
        Organization $organization,
    ): OrganizationMembership {
        DemoGate::denyOnDemoOrganization(
            $organization->id,
            __('Demo mode does not allow changing member roles.'),
        );

        $this->assertMembershipInOrganization($membership, $organization);
        $this->assertActorCanManage($actor, $organization);

        if (! $membership->isActive()) {
            throw ValidationException::withMessages([
                'member' => __('This membership is no longer active.'),
            ]);
        }

        if ($membership->role === $newRole) {
            return $membership;
        }

        return DB::transaction(function () use ($membership, $newRole): OrganizationMembership {
            $membership = OrganizationMembership::query()
                ->whereKey($membership->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ownerGuard->assertCanChangeRole($membership, $newRole);

            $membership->update(['role' => $newRole]);

            return $membership->refresh();
        });
    }

    private function assertMembershipInOrganization(
        OrganizationMembership $membership,
        Organization $organization,
    ): void {
        if ($membership->organization_id !== $organization->id) {
            throw ValidationException::withMessages([
                'member' => __('Member not found in this organization.'),
            ]);
        }
    }

    private function assertActorCanManage(User $actor, Organization $organization): void
    {
        $actorMembership = $actor->membershipIn($organization);

        if ($actorMembership === null || ! $actorMembership->role->canManageMembers()) {
            throw ValidationException::withMessages([
                'member' => __('You are not authorized to manage members.'),
            ]);
        }
    }
}
