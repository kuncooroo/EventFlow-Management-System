<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UnassignMemberFromEvent
{
    public function handle(Event $event, OrganizationMembership $assignee, User $actor): void
    {
        Gate::forUser($actor)->authorize('manageAssignments', $event);

        if ($assignee->organization_id !== $event->organization_id) {
            throw ValidationException::withMessages([
                'assignee' => 'Assignee must belong to the same organization.',
            ]);
        }

        $assignment = $event->assignments()
            ->where('organization_membership_id', $assignee->id)
            ->first();

        if (! $assignment) {
            throw ValidationException::withMessages([
                'assignee' => 'Member is not assigned to this event.',
            ]);
        }

        $assignment->delete();
    }
}
