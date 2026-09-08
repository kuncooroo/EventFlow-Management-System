<?php

namespace App\Notifications\Organizations;

use App\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

class OrganizationInvitationNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        public readonly OrganizationInvitation $invitation,
        public readonly string $rawToken,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $organization = $this->invitation->organization;
        $acceptUrl = route('app.invitations.show', ['token' => $this->rawToken]);

        return (new MailMessage)
            ->subject(__('Invitation to join :organization', ['organization' => $organization->name]))
            ->greeting(__('Hello!'))
            ->line(__('You have been invited to join :organization as an :role on EventFlow.', [
                'organization' => $organization->name,
                'role' => $this->invitation->role->label(),
            ]))
            ->action(__('Accept Invitation'), $acceptUrl)
            ->line(__('This invitation will expire in 7 days.'))
            ->line(__('If you did not expect this invitation, no further action is required.'));
    }

    public function failed(Throwable $e): void
    {
        report($e);
    }
}
