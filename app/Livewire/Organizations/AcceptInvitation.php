<?php

namespace App\Livewire\Organizations;

use App\Actions\Organizations\AcceptInvitation as AcceptInvitationAction;
use App\Models\OrganizationInvitation;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Organization Invitation')]
class AcceptInvitation extends Component
{
    public string $token;

    public ?OrganizationInvitation $invitation = null;

    public ?string $errorMessage = null;

    public function mount(string $token): void
    {
        $this->token = $token;
        $tokenHash = OrganizationInvitation::hashToken($token);

        $invitation = OrganizationInvitation::query()
            ->with(['organization', 'invitedBy'])
            ->where('token_hash', $tokenHash)
            ->first();

        if ($invitation === null) {
            $this->errorMessage = __('This invitation link is invalid.');

            return;
        }

        if ($invitation->isAccepted()) {
            $this->errorMessage = __('This invitation has already been accepted.');
        } elseif ($invitation->isRevoked()) {
            $this->errorMessage = __('This invitation has been revoked by the organization owner.');
        } elseif ($invitation->isExpired()) {
            $this->errorMessage = __('This invitation link has expired.');
        }

        $this->invitation = $invitation;
    }

    public function accept(AcceptInvitationAction $acceptInvitation): void
    {
        if (! auth()->check()) {
            redirect()->route('login');

            return;
        }

        if ($this->invitation === null || ! $this->invitation->isPending()) {
            return;
        }

        try {
            $acceptInvitation->handle($this->token, auth()->user());

            session()->flash('status', __('You have joined :organization!', [
                'organization' => $this->invitation->organization->name,
            ]));

            $this->redirectRoute('app.dashboard');
        } catch (ValidationException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.organizations.accept-invitation');
    }
}
