<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\User;
use App\Support\Organization\OrganizationContext;

class EventPolicy
{
    public function __construct(
        private OrganizationContext $context
    ) {}

    public function viewAny(User $user): bool
    {
        $membership = $this->getCurrentMembership($user);

        return $membership !== null;
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

    public function create(User $user): bool
    {
        $membership = $this->getCurrentMembership($user);
        if (! $membership) {
            return false;
        }

        return in_array($membership->role, [OrganizationRole::Owner, OrganizationRole::Admin]);
    }

    public function update(User $user, Event $event): bool
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

        if ($membership->role === OrganizationRole::EventManager) {
            return $event->assignments()->where('organization_membership_id', $membership->id)->exists();
        }

        return false;
    }

    public function delete(User $user, Event $event): bool
    {
        if (! $this->isSameOrganization($event)) {
            return false;
        }

        $membership = $this->getCurrentMembership($user);
        if (! $membership) {
            return false;
        }

        return in_array($membership->role, [OrganizationRole::Owner, OrganizationRole::Admin]);
    }

    public function publish(User $user, Event $event): bool
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

        if ($membership->role === OrganizationRole::EventManager) {
            return $event->assignments()->where('organization_membership_id', $membership->id)->exists();
        }

        return false;
    }

    public function manageAssignments(User $user, Event $event): bool
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

        return false;
    }

    public function checkIn(User $user, Event $event): bool
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

        if (in_array($membership->role, [OrganizationRole::EventManager, OrganizationRole::Staff])) {
            return $event->assignments()->where('organization_membership_id', $membership->id)->exists();
        }

        return false;
    }

    public function cancelRegistration(User $user, Event $event): bool
    {
        return $this->canOperateOnAssigned($user, $event, [OrganizationRole::EventManager]);
    }

    public function markOngoing(User $user, Event $event): bool
    {
        return $this->canOperateOnAssigned($user, $event, [OrganizationRole::EventManager]);
    }

    public function completeEvent(User $user, Event $event): bool
    {
        return $this->canOperateOnAssigned($user, $event, [OrganizationRole::EventManager]);
    }

    public function cancelEvent(User $user, Event $event): bool
    {
        return $this->canOperateOnAssigned($user, $event, [OrganizationRole::EventManager]);
    }

    public function archive(User $user, Event $event): bool
    {
        return $this->canOperateOnAssigned($user, $event, [OrganizationRole::EventManager]);
    }

    private function isSameOrganization(Event $event): bool
    {
        $current = $this->context->current();

        return $current !== null && $current->id === $event->organization_id;
    }

    /**
     * Owner/Admin always allowed; EventManager must be assigned to the event.
     *
     * @param  array<int, OrganizationRole>  $assignedRoles
     */
    private function canOperateOnAssigned(User $user, Event $event, array $assignedRoles): bool
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

        if (in_array($membership->role, $assignedRoles, true)) {
            return $event->assignments()->where('organization_membership_id', $membership->id)->exists();
        }

        return false;
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
