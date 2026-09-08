<?php

namespace App\Actions\Organizations;

use App\Models\OrganizationInvitation;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Support\Organization\OrganizationContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptInvitation
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
    ) {}

    public function handle(string $rawToken, User $user): OrganizationMembership
    {
        $tokenHash = OrganizationInvitation::hashToken($rawToken);

        $invitation = OrganizationInvitation::query()
            ->where('token_hash', $tokenHash)
            ->first();

        if ($invitation === null) {
            throw ValidationException::withMessages([
                'invitation' => __('This invitation link is invalid.'),
            ]);
        }

        if (! $invitation->isPending()) {
            if ($invitation->isAccepted()) {
                throw ValidationException::withMessages([
                    'invitation' => __('This invitation has already been accepted.'),
                ]);
            }

            if ($invitation->isRevoked()) {
                throw ValidationException::withMessages([
                    'invitation' => __('This invitation has been revoked.'),
                ]);
            }

            throw ValidationException::withMessages([
                'invitation' => __('This invitation has expired.'),
            ]);
        }

        if (strcasecmp($invitation->email, $user->email) !== 0) {
            throw ValidationException::withMessages([
                'invitation' => __('This invitation was sent to :email. You are signed in as :current.', [
                    'email' => $invitation->email,
                    'current' => $user->email,
                ]),
            ]);
        }

        $membership = DB::transaction(function () use ($invitation, $user) {
            $invitation->update([
                'accepted_at' => now(),
                'accepted_by_user_id' => $user->id,
            ]);

            return OrganizationMembership::query()->updateOrCreate(
                [
                    'organization_id' => $invitation->organization_id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => $invitation->role,
                    'joined_at' => now(),
                    'removed_at' => null,
                ]
            );
        });

        $this->organizationContext->set($invitation->organization);

        return $membership;
    }
}
