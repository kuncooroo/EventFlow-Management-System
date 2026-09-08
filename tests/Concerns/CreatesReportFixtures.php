<?php

namespace Tests\Concerns;

use App\Enums\OrganizationRole;
use App\Models\CheckIn;
use App\Models\Event;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Registration;
use App\Models\TicketType;
use App\Models\User;
use App\Support\Organization\OrganizationContext;

trait CreatesReportFixtures
{
    /**
     * Owner + organization with the context session pointed at the org.
     *
     * @return array{0: User, 1: Organization}
     */
    protected function ownerWithOrganization(): array
    {
        $user = User::factory()->create();
        $organization = $this->createOrganizationForUser($user);

        session([OrganizationContext::SESSION_KEY => $organization->id]);

        return [$user, $organization];
    }

    protected function makeEvent(Organization $organization, string $name = 'Reports Event'): Event
    {
        return Event::factory()->for($organization)->published()->create(['name' => $name]);
    }

    protected function makeType(Event $event, string $name, int $capacity): TicketType
    {
        return TicketType::factory()->for($event)->capped($capacity)->create(['name' => $name]);
    }

    /**
     * @param  'confirmed'|'cancelled'  $status
     */
    protected function registerAttendee(
        Event $event,
        TicketType $type,
        string $status = 'confirmed',
        ?bool $checkedIn = false,
    ): Registration {
        $registration = Registration::factory()
            ->for($event)
            ->for($type, 'ticketType')
            ->{$status}()
            ->create();

        if ($checkedIn) {
            CheckIn::factory()->for($registration)->create(['checked_in_at' => now()]);
        }

        return $registration;
    }

    /**
     * Adds a member with the given role and returns [user, membership].
     */
    protected function memberWithRole(
        Organization $organization,
        string $role = OrganizationRole::Viewer->value,
    ): array {
        $user = User::factory()->create();

        return [$user, $this->addMemberToOrganization($organization, $user, OrganizationRole::from($role))];
    }

    protected function assignEvent(Event $event, Organization $organization, OrganizationMembership $membership): void
    {
        $event->assignments()->create([
            'organization_id' => $organization->id,
            'organization_membership_id' => $membership->id,
        ]);
    }
}
