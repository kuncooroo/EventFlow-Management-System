<?php

namespace App\Livewire\Events;

use App\Actions\Events\AssignMemberToEvent;
use App\Actions\Events\UnassignMemberFromEvent;
use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\OrganizationMembership;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AssignmentManager extends Component
{
    public Event $event;

    public $assignee_id = '';

    public function mount(Event $event)
    {
        $this->event = $event;
        Gate::authorize('manageAssignments', $this->event);
    }

    public function assignMember(AssignMemberToEvent $assignAction)
    {
        Gate::authorize('manageAssignments', $this->event);

        $this->validate([
            'assignee_id' => 'required|exists:organization_memberships,id',
        ]);

        $assignee = OrganizationMembership::where('organization_id', $this->event->organization_id)
            ->findOrFail($this->assignee_id);

        try {
            $assignAction->handle($this->event, $assignee, auth()->user());
            $this->assignee_id = '';
            session()->flash('status', 'Member assigned successfully.');
        } catch (ValidationException $e) {
            $this->addError('assignee_id', $e->getMessage());
        }
    }

    public function unassignMember(UnassignMemberFromEvent $unassignAction, int $membershipId)
    {
        Gate::authorize('manageAssignments', $this->event);

        $assignee = OrganizationMembership::where('organization_id', $this->event->organization_id)
            ->findOrFail($membershipId);

        try {
            $unassignAction->handle($this->event, $assignee, auth()->user());
            session()->flash('status', 'Member unassigned successfully.');
        } catch (ValidationException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $assignments = $this->event->assignments()->with('membership.user')->get();

        $eligibleMembers = OrganizationMembership::active()
            ->where('organization_id', $this->event->organization_id)
            ->whereNotIn('role', [OrganizationRole::Owner, OrganizationRole::Admin])
            ->whereNotIn('id', $assignments->pluck('organization_membership_id'))
            ->with('user')
            ->get();

        return view('livewire.events.assignment-manager', [
            'assignments' => $assignments,
            'eligibleMembers' => $eligibleMembers,
        ]);
    }
}
