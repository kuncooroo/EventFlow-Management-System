<?php

namespace App\Support\Organization;

use App\Enums\OrganizationRole;
use App\Models\OrganizationMembership;
use Illuminate\Validation\ValidationException;

class OrganizationOwnerGuard
{
    public function activeOwnerCount(int $organizationId, bool $lockForUpdate = false): int
    {
        $query = OrganizationMembership::query()
            ->where('organization_id', $organizationId)
            ->where('role', OrganizationRole::Owner)
            ->whereNull('removed_at');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->count();
    }

    public function assertCanChangeRole(OrganizationMembership $membership, OrganizationRole $newRole): void
    {
        if ($membership->role === OrganizationRole::Owner && $newRole !== OrganizationRole::Owner) {
            $ownerCount = $this->activeOwnerCount($membership->organization_id, lockForUpdate: true);

            if ($ownerCount <= 1) {
                throw ValidationException::withMessages([
                    'role' => __('The organization must have at least one owner.'),
                ]);
            }
        }
    }

    public function assertCanRemove(OrganizationMembership $membership): void
    {
        if ($membership->role !== OrganizationRole::Owner) {
            return;
        }

        $ownerCount = $this->activeOwnerCount($membership->organization_id, lockForUpdate: true);

        if ($ownerCount <= 1) {
            throw ValidationException::withMessages([
                'member' => __('The organization must have at least one owner.'),
            ]);
        }
    }
}
