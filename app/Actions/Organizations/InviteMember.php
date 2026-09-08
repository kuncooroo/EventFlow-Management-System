<?php

namespace App\Actions\Organizations;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\Organizations\OrganizationInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InviteMember
{
    /**
     * @return array{invitation: OrganizationInvitation, token: string}
     */
    public function handle(
        Organization $organization,
        string $email,
        OrganizationRole $role,
        User $actor,
    ): array {
        $email = strtolower(trim($email));

        $this->assertActorCanManage($actor, $organization);
        $this->assertNotAlreadyActiveMember($organization, $email);

        $rawToken = Str::random(40);
        $tokenHash = OrganizationInvitation::hashToken($rawToken);

        $invitation = DB::transaction(function () use ($organization, $actor, $email, $role, $tokenHash) {
            OrganizationInvitation::query()
                ->where('organization_id', $organization->id)
                ->where('email', $email)
                ->delete();

            return OrganizationInvitation::query()->create([
                'organization_id' => $organization->id,
                'invited_by_user_id' => $actor->id,
                'email' => $email,
                'role' => $role,
                'token_hash' => $tokenHash,
                'expires_at' => now()->addDays(7),
            ]);
        });

        Notification::route('mail', $email)
            ->notify(new OrganizationInvitationNotification($invitation, $rawToken));

        return [
            'invitation' => $invitation,
            'token' => $rawToken,
        ];
    }

    private function assertActorCanManage(User $actor, Organization $organization): void
    {
        $actorMembership = $actor->membershipIn($organization);

        if ($actorMembership === null || ! $actorMembership->role->canManageMembers()) {
            throw ValidationException::withMessages([
                'email' => __('You are not authorized to invite members to this organization.'),
            ]);
        }
    }

    private function assertNotAlreadyActiveMember(Organization $organization, string $email): void
    {
        $isMember = $organization->activeMemberships()
            ->whereHas('user', function ($query) use ($email) {
                $query->where('email', $email);
            })
            ->exists();

        if ($isMember) {
            throw ValidationException::withMessages([
                'email' => __('This user is already an active member of this organization.'),
            ]);
        }
    }
}
