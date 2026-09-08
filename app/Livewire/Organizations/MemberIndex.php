<?php

namespace App\Livewire\Organizations;

use App\Actions\Organizations\ChangeMemberRole;
use App\Actions\Organizations\InviteMember;
use App\Actions\Organizations\RemoveMember;
use App\Actions\Organizations\RevokeInvitation;
use App\Enums\OrganizationRole;
use App\Http\Requests\Organizations\UpdateMemberRoleRequest;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMembership;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Members')]
class MemberIndex extends Component
{
    use AuthorizesRequests;

    public Organization $organization;

    public bool $canManageMembers = false;

    public ?string $statusMessage = null;

    public bool $showInviteModal = false;

    public string $inviteEmail = '';

    public string $inviteRole = 'staff';

    public function mount(OrganizationContext $organizationContext): void
    {
        $user = auth()->user();
        $organization = $organizationContext->resolveForUser($user);

        if ($organization === null) {
            abort(403);
        }

        $this->organization = $organization;
        $this->authorize('viewMembers', $organization);
        $this->canManageMembers = $user->can('manageMembers', $organization);
        $this->statusMessage = session('status');
    }

    public function openInviteModal(): void
    {
        $this->authorize('manageMembers', $this->organization);
        $this->resetValidation();
        $this->inviteEmail = '';
        $this->inviteRole = OrganizationRole::Staff->value;
        $this->showInviteModal = true;
    }

    public function closeInviteModal(): void
    {
        $this->showInviteModal = false;
        $this->resetValidation();
    }

    public function sendInvite(InviteMember $inviteMember): void
    {
        $this->authorize('manageMembers', $this->organization);

        $validated = $this->validate([
            'inviteEmail' => ['required', 'string', 'email', 'max:254'],
            'inviteRole' => ['required', 'string', Rule::enum(OrganizationRole::class)],
        ], [], [
            'inviteEmail' => 'email address',
            'inviteRole' => 'role',
        ]);

        $email = $validated['inviteEmail'];

        $inviteMember->handle(
            $this->organization,
            $email,
            OrganizationRole::from($validated['inviteRole']),
            auth()->user(),
        );

        $this->showInviteModal = false;
        $this->inviteEmail = '';
        $this->statusMessage = __('Invitation sent to :email.', ['email' => $email]);
    }

    public function revokeInvite(int $invitationId, RevokeInvitation $revokeInvitation): void
    {
        $this->authorize('manageMembers', $this->organization);

        $invitation = OrganizationInvitation::query()
            ->where('organization_id', $this->organization->id)
            ->findOrFail($invitationId);

        $revokeInvitation->handle($invitation, auth()->user(), $this->organization);

        $this->statusMessage = __('Invitation revoked successfully.');
    }

    public function changeRole(int $membershipId, string $role, ChangeMemberRole $changeMemberRole): void
    {
        $membership = $this->findMembership($membershipId);

        $this->authorize('update', $membership);

        $validated = validator(
            ['role' => $role],
            (new UpdateMemberRoleRequest)->rules(),
        )->validate();

        $changeMemberRole->handle(
            $membership,
            OrganizationRole::from($validated['role']),
            auth()->user(),
            $this->organization,
        );

        $this->statusMessage = __('Role updated successfully.');
    }

    public function removeMember(int $membershipId, RemoveMember $removeMember): void
    {
        $membership = $this->findMembership($membershipId);

        $this->authorize('delete', $membership);

        $removeMember->handle($membership, auth()->user(), $this->organization);

        $this->statusMessage = __('Member removed successfully.');
    }

    public function render(): View
    {
        $memberships = OrganizationMembership::query()
            ->with('user')
            ->where('organization_id', $this->organization->id)
            ->whereNull('removed_at')
            ->orderBy('role')
            ->orderBy('joined_at')
            ->get();

        $pendingInvitations = $this->canManageMembers
            ? OrganizationInvitation::query()
                ->with('invitedBy')
                ->where('organization_id', $this->organization->id)
                ->pending()
                ->latest()
                ->get()
            : collect();

        return view('livewire.organizations.member-index', [
            'memberships' => $memberships,
            'pendingInvitations' => $pendingInvitations,
            'assignableRoles' => OrganizationRole::assignable(),
        ]);
    }

    private function findMembership(int $membershipId): OrganizationMembership
    {
        return OrganizationMembership::query()
            ->where('organization_id', $this->organization->id)
            ->whereNull('removed_at')
            ->findOrFail($membershipId);
    }
}
