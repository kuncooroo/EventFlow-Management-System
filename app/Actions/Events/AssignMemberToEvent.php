<?php

namespace App\Actions\Events;

use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AssignMemberToEvent
{
    public function handle(Event $event, OrganizationMembership $assignee, User $actor): void
    {
        Gate::forUser($actor)->authorize('manageAssignments', $event);

        if ($assignee->organization_id !== $event->organization_id) {
            throw ValidationException::withMessages([
                'assignee' => 'Assignee must belong to the same organization.',
            ]);
        }

        if (! $assignee->isActive()) {
            throw ValidationException::withMessages([
                'assignee' => 'Assignee is no longer an active member of this organization.',
            ]);
        }

        if (in_array($assignee->role, [OrganizationRole::Owner, OrganizationRole::Admin])) {
            throw ValidationException::withMessages([
                'assignee' => 'Owner and Admin roles have global access and do not require assignment.',
            ]);
        }

        $alreadyAssigned = $event->assignments()
            ->where('organization_membership_id', $assignee->id)
            ->exists();

        if ($alreadyAssigned) {
            throw ValidationException::withMessages([
                'assignee' => 'Member is already assigned to this event.',
            ]);
        }

        $event->assignments()->create([
            'organization_id' => $event->organization_id,
            'organization_membership_id' => $assignee->id,
            'assigned_by_user_id' => $actor->id,
        ]);
    }
}
