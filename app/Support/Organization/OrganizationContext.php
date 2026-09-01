<?php

namespace App\Support\Organization;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Session;

class OrganizationContext
{
    public const SESSION_KEY = 'current_organization_id';

    /**
     * @return Collection<int, Organization>
     */
    public function accessibleOrganizations(User $user): Collection
    {
        return Organization::query()
            ->whereHas('memberships', function ($query) use ($user): void {
                $query->where('user_id', $user->id)->whereNull('removed_at');
            })
            ->orderBy('name')
            ->get();
    }

    public function userHasAccess(User $user, Organization $organization): bool
    {
        return $user->activeOrganizationMemberships()
            ->where('organization_id', $organization->id)
            ->exists();
    }

    public function current(): ?Organization
    {
        $organizationId = Session::get(self::SESSION_KEY);

        if (! $organizationId) {
            return null;
        }

        return Organization::query()->find($organizationId);
    }

    public function set(Organization $organization): void
    {
        Session::put(self::SESSION_KEY, $organization->id);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function resolveForUser(User $user): ?Organization
    {
        $accessible = $this->accessibleOrganizations($user);

        if ($accessible->isEmpty()) {
            $this->clear();

            return null;
        }

        $current = $this->current();

        if ($current !== null && $accessible->contains('id', $current->id)) {
            return $current;
        }

        $organization = $accessible->first();
        $this->set($organization);

        return $organization;
    }
}
