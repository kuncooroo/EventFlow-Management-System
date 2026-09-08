<?php

namespace App\Queries\Reports;

use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AccessibleEventsQuery
{
    /**
     * Events the user may access within the organization (DASH-001 / FR-RPT-005):
     * Owner/Admin see all events; other members only see assigned events.
     */
    public function __invoke(Organization $organization, User $user): Builder
    {
        $query = Event::query()->where('organization_id', $organization->id);

        $membership = $organization->memberships()
            ->where('user_id', $user->id)
            ->active()
            ->first();

        if ($membership === null) {
            return $query->whereRaw('1 = 0');
        }

        if (in_array($membership->role, [OrganizationRole::Owner, OrganizationRole::Admin], true)) {
            return $query;
        }

        return $query->whereHas(
            'assignments',
            fn (Builder $assignment) => $assignment->where('organization_membership_id', $membership->id),
        );
    }
}
