<?php

namespace Tests\Feature\Organizations;

use App\Actions\Organizations\AcceptInvitation as AcceptInvitationAction;
use App\Actions\Organizations\InviteMember;
use App\Actions\Organizations\RevokeInvitation;
use App\Enums\OrganizationRole;
use App\Livewire\Organizations\MemberIndex;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\Organizations\OrganizationInvitationNotification;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithOrganizations;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use InteractsWithOrganizations;
    use RefreshDatabase;

    #[DataProvider('managingRoles')]
    public function test_authorized_roles_can_invite_member(OrganizationRole $role): void
    {
        Notification::fake();

        $actor = User::factory()->create();
        $organization = $this->createOrganizationForUser($actor, role: $role);

        session([OrganizationContext::SESSION_KEY => $organization->id]);

        Livewire::actingAs($actor)
            ->test(MemberIndex::class)
            ->set('inviteEmail', 'newmember@example.com')
            ->set('inviteRole', OrganizationRole::EventManager->value)
            ->call('sendInvite')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('organization_invitations', [
            'organization_id' => $organization->id,
            'email' => 'newmember@example.com',
            'role' => OrganizationRole::EventManager->value,
            'invited_by_user_id' => $actor->id,
        ]);

        Notification::assertSentTo(
            Notification::route('mail', 'newmember@example.com'),
            OrganizationInvitationNotification::class
        );
    }

    /**
     * @return array<string, array{OrganizationRole}>
     */
    public static function managingRoles(): array
    {
        return [
            'owner' => [OrganizationRole::Owner],
            'admin' => [OrganizationRole::Admin],
        ];
    }

    #[DataProvider('nonManagingRoles')]
    public function test_unauthorized_roles_cannot_invite_member(OrganizationRole $role): void
    {
        Notification::fake();

        $actor = User::factory()->create();
        $organization = $this->createOrganizationForUser($actor, role: $role);

        $this->expectException(ValidationException::class);

        app(InviteMember::class)->handle(
            $organization,
            'newmember@example.com',
            OrganizationRole::Staff,
            $actor,
        );
    }

    /**
     * @return array<string, array{OrganizationRole}>
     */
    public static function nonManagingRoles(): array
    {
        return [
            'event manager' => [OrganizationRole::EventManager],
            'staff' => [OrganizationRole::Staff],
            'viewer' => [OrganizationRole::Viewer],
        ];
    }

    public function test_cannot_invite_existing_active_member(): void
    {
        $actor = User::factory()->create();
        $organization = $this->createOrganizationForUser($actor, role: OrganizationRole::Owner);

        $existingMember = User::factory()->create(['email' => 'existing@example.com']);
        $this->addMemberToOrganization($organization, $existingMember, OrganizationRole::Staff);

        $this->expectException(ValidationException::class);

        app(InviteMember::class)->handle(
            $organization,
            'existing@example.com',
            OrganizationRole::Admin,
            $actor,
        );
    }

    public function test_reinviting_same_email_replaces_previous_pending_invitation(): void
    {
        Notification::fake();

        $actor = User::factory()->create();
        $organization = $this->createOrganizationForUser($actor, role: OrganizationRole::Owner);

        app(InviteMember::class)->handle(
            $organization,
            'invitee@example.com',
            OrganizationRole::Staff,
            $actor,
        );

        $firstInvite = OrganizationInvitation::query()->where('email', 'invitee@example.com')->first();
        $this->assertNotNull($firstInvite);

        app(InviteMember::class)->handle(
            $organization,
            'invitee@example.com',
            OrganizationRole::EventManager,
            $actor,
        );

        $this->assertDatabaseCount('organization_invitations', 1);

        $secondInvite = OrganizationInvitation::query()->where('email', 'invitee@example.com')->first();
        $this->assertNotNull($secondInvite);
        $this->assertSame(OrganizationRole::EventManager, $secondInvite->role);
    }

    public function test_invitee_can_view_invitation_page(): void
    {
        $inviter = User::factory()->create();
        $organization = $this->createOrganizationForUser($inviter, ['name' => 'Acme Corp'], role: OrganizationRole::Owner);

        $res = app(InviteMember::class)->handle(
            $organization,
            'invitee@example.com',
            OrganizationRole::EventManager,
            $inviter,
        );

        $this->get(route('app.invitations.show', ['token' => $res['token']]))
            ->assertOk()
            ->assertSee('Acme Corp', false)
            ->assertSee('Event Manager', false);
    }

    public function test_invitee_can_accept_invitation_and_join_organization(): void
    {
        $inviter = User::factory()->create();
        $organization = $this->createOrganizationForUser($inviter, ['name' => 'Acme Corp'], role: OrganizationRole::Owner);

        $res = app(InviteMember::class)->handle(
            $organization,
            'invitee@example.com',
            OrganizationRole::EventManager,
            $inviter,
        );

        $invitee = User::factory()->create(['email' => 'invitee@example.com']);

        $userCountBefore = User::query()->count();

        $membership = app(AcceptInvitationAction::class)->handle($res['token'], $invitee);

        $this->assertSame($organization->id, $membership->organization_id);
        $this->assertSame($invitee->id, $membership->user_id);
        $this->assertSame(OrganizationRole::EventManager, $membership->role);

        $this->assertDatabaseCount('users', $userCountBefore);

        $invitation = $res['invitation']->refresh();
        $this->assertNotNull($invitation->accepted_at);
        $this->assertSame($invitee->id, $invitation->accepted_by_user_id);

        $this->assertSame($organization->id, session(OrganizationContext::SESSION_KEY));
    }

    public function test_cannot_accept_expired_invitation(): void
    {
        $inviter = User::factory()->create();
        $organization = $this->createOrganizationForUser($inviter, role: OrganizationRole::Owner);

        $res = app(InviteMember::class)->handle(
            $organization,
            'invitee@example.com',
            OrganizationRole::Staff,
            $inviter,
        );

        $res['invitation']->update(['expires_at' => now()->subDay()]);

        $invitee = User::factory()->create(['email' => 'invitee@example.com']);

        $this->expectException(ValidationException::class);

        app(AcceptInvitationAction::class)->handle($res['token'], $invitee);
    }

    public function test_cannot_accept_revoked_invitation(): void
    {
        $inviter = User::factory()->create();
        $organization = $this->createOrganizationForUser($inviter, role: OrganizationRole::Owner);

        $res = app(InviteMember::class)->handle(
            $organization,
            'invitee@example.com',
            OrganizationRole::Staff,
            $inviter,
        );

        app(RevokeInvitation::class)->handle($res['invitation'], $inviter, $organization);

        $invitee = User::factory()->create(['email' => 'invitee@example.com']);

        $this->expectException(ValidationException::class);

        app(AcceptInvitationAction::class)->handle($res['token'], $invitee);
    }

    public function test_cannot_accept_invitation_with_mismatched_email(): void
    {
        $inviter = User::factory()->create();
        $organization = $this->createOrganizationForUser($inviter, role: OrganizationRole::Owner);

        $res = app(InviteMember::class)->handle(
            $organization,
            'target@example.com',
            OrganizationRole::Staff,
            $inviter,
        );

        $differentUser = User::factory()->create(['email' => 'other@example.com']);

        $this->expectException(ValidationException::class);

        app(AcceptInvitationAction::class)->handle($res['token'], $differentUser);
    }

    public function test_owner_can_revoke_pending_invitation(): void
    {
        $actor = User::factory()->create();
        $organization = $this->createOrganizationForUser($actor, role: OrganizationRole::Owner);

        $res = app(InviteMember::class)->handle(
            $organization,
            'invitee@example.com',
            OrganizationRole::Staff,
            $actor,
        );

        session([OrganizationContext::SESSION_KEY => $organization->id]);

        Livewire::actingAs($actor)
            ->test(MemberIndex::class)
            ->call('revokeInvite', $res['invitation']->id)
            ->assertHasNoErrors();

        $invitation = $res['invitation']->refresh();
        $this->assertNotNull($invitation->revoked_at);
    }
}
