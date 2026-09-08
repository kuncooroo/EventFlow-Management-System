<?php

namespace Tests\Feature\Security;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\User;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithOrganizations;
use Tests\TestCase;

class OrganizationIsolationTest extends TestCase
{
    use InteractsWithOrganizations;
    use RefreshDatabase;

    private Organization $foreignOrganization;

    private Event $foreignEvent;

    private Registration $foreignRegistration;

    /**
     * Tenant isolation contract: a member of Org A must never reach resources
     * owned by Org B through parameterized routes.
     */
    public function test_member_cannot_access_foreign_organizations_events(): void
    {
        [$user, $ownOrganization] = $this->makeForeignFixture();

        $routes = [
            'event edit' => fn () => route('app.events.edit', $this->foreignEvent),
            'event setup' => fn () => route('app.events.setup', $this->foreignEvent),
            'event dashboard' => fn () => route('app.events.dashboard', $this->foreignEvent),
            'attendees index' => fn () => route('app.events.attendees.index', $this->foreignEvent),
            'attendee show' => fn () => route('app.events.attendees.show', [$this->foreignEvent, $this->foreignRegistration]),
            'check-in index' => fn () => route('app.events.check-in.index', $this->foreignEvent),
            'attendees export' => fn () => route('app.reports.export', ['type' => 'attendees', 'event_id' => $this->foreignEvent->id]),
        ];

        foreach ($routes as $label => $url) {
            $response = $this->actingAs($user)
                ->withSession([OrganizationContext::SESSION_KEY => $ownOrganization->id])
                ->get($url());

            $this->assertTrue(
                in_array($response->status(), [403, 404], true),
                "{$label} did not reject foreign organization access (got {$response->status()})"
            );
        }
    }

    public function test_organizations_do_not_leak_names_to_foreign_members(): void
    {
        [$user, $ownOrganization] = $this->makeForeignFixture(['name' => 'Acme Events']);

        $this->actingAs($user)
            ->withSession([OrganizationContext::SESSION_KEY => $ownOrganization->id])
            ->get(route('app.dashboard'))
            ->assertOk()
            ->assertSee('Acme Events', false)
            ->assertDontSee('Private Rival', false);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function makeForeignFixture(array $ownOrganizationAttributes = []): array
    {
        $user = User::factory()->create();
        $ownOrganization = $this->createOrganizationForUser($user, $ownOrganizationAttributes);

        $foreignUser = User::factory()->create();
        $this->foreignOrganization = $this->createOrganizationForUser($foreignUser, ['name' => 'Private Rival']);
        $this->foreignEvent = Event::factory()->for($this->foreignOrganization)->create();
        $this->foreignRegistration = Registration::factory()->for($this->foreignEvent)->create();

        return [$user, $ownOrganization];
    }
}
