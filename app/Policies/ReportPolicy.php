<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\User;
use App\Support\Organization\OrganizationContext;

class ReportPolicy
{
    public function __construct(
        private OrganizationContext $context
    ) {}

    /**
     * Any active member may open the reports module. Data is further scoped to
     * accessible events by the report queries (FR-RPT-005).
     */
    public function viewAny(User $user): bool
    {
        return $this->getCurrentMembership($user) !== null;
    }

    public function view(User $user, Event $event): bool
    {
        if (! $this->isSameOrganization($event)) {
            return false;
        }

        $membership = $this->getCurrentMembership($user);
        if (! $membership) {
            return false;
        }

        if (in_array($membership->role, [OrganizationRole::Owner, OrganizationRole::Admin])) {
            return true;
        }

        return $event->assignments()->where('organization_membership_id', $membership->id)->exists();
    }

    private function isSameOrganization(Event $event): bool
    {
        $current = $this->context->current();

        return $current !== null && $current->id === $event->organization_id;
    }

    private function getCurrentMembership(User $user)
    {
        $current = $this->context->current();
        if (! $current) {
            return null;
        }

        return $current->memberships()->where('user_id', $user->id)->active()->first();
    }
}
