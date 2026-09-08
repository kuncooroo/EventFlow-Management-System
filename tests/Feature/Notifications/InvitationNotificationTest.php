<?php

namespace Tests\Feature\Notifications;

use App\Actions\Organizations\InviteMember;
use App\Enums\OrganizationRole;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\Organizations\OrganizationInvitationNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\InteractsWithOrganizations;
use Tests\TestCase;

class InvitationNotificationTest extends TestCase
{
    use InteractsWithOrganizations;
    use RefreshDatabase;

    public function test_invite_sends_queued_notification_to_invitee_email(): void
    {
        Notification::fake();

        $actor = User::factory()->create();
        $organization = $this->createOrganizationForUser($actor, role: OrganizationRole::Owner);

        app(InviteMember::class)->handle(
            $organization,
            'invitee@example.com',
            OrganizationRole::EventManager,
            $actor,
        );

        Notification::assertSentTo(
            Notification::route('mail', 'invitee@example.com'),
            OrganizationInvitationNotification::class
        );
    }

    public function test_invitation_email_contains_organization_role_and_accept_url(): void
    {
        Notification::fake();

        $actor = User::factory()->create();
        $organization = $this->createOrganizationForUser($actor, role: OrganizationRole::Owner);

        app(InviteMember::class)->handle(
            $organization,
            'invitee@example.com',
            OrganizationRole::EventManager,
            $actor,
        );

        $invitation = $organization->invitations()->latest('id')->first();

        $this->assertNotNull($invitation);

        $mail = (new OrganizationInvitationNotification($invitation, 'raw-token'))->toMail(new AnonymousNotifiable);
        $html = $mail->render();

        $this->assertStringContainsString($organization->name, $html);
        $this->assertStringContainsString(OrganizationRole::EventManager->label(), $html);
        $this->assertStringContainsString(route('app.invitations.show', ['token' => 'raw-token']), $html);
    }

    public function test_invitation_notification_is_queued_after_commit(): void
    {
        $invitation = OrganizationInvitation::factory()->create();

        $notification = new OrganizationInvitationNotification($invitation, 'raw-token');

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertInstanceOf(ShouldQueueAfterCommit::class, $notification);

        $job = new SendQueuedNotifications(
            Notification::route('mail', 'invitee@example.com'),
            $notification
        );

        $this->assertTrue($job->afterCommit);
    }
}
