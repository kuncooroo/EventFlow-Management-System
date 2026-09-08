<?php

namespace Tests\Feature\Reports;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Policies\ReportPolicy;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesReportFixtures;
use Tests\Concerns\InteractsWithOrganizations;
use Tests\TestCase;

class ReportPolicyTest extends TestCase
{
    use CreatesReportFixtures;
    use InteractsWithOrganizations;
    use RefreshDatabase;

    public function test_owner_and_admin_can_view_any_event_report(): void
    {
        foreach ([OrganizationRole::Owner, OrganizationRole::Admin] as $role) {
            [$user, $org, $membership] = $this->memberInOrg($role);
            $event = $this->makeEvent($org);

            $this->assertTrue($user->can('viewAny', [ReportPolicy::class]));
            $this->assertTrue($user->can('view', [ReportPolicy::class, $event]));
        }
    }

    public function test_management_roles_view_only_assigned_events(): void
    {
        foreach ([OrganizationRole::EventManager, OrganizationRole::Staff] as $role) {
            [$user, $org, $membership] = $this->memberInOrg($role);

            $assigned = $this->makeEvent($org, 'Assigned');
            $unassigned = $this->makeEvent($org, 'Unassigned');
            $this->assignEvent($assigned, $org, $membership);

            $this->assertTrue($user->can('viewAny', [ReportPolicy::class]));
            $this->assertTrue($user->can('view', [ReportPolicy::class, $assigned]));
            $this->assertFalse($user->can('view', [ReportPolicy::class, $unassigned]));
        }
    }

    public function test_viewer_can_view_only_assigned_events(): void
    {
        [$user, $org, $membership] = $this->memberInOrg(OrganizationRole::Viewer);

        $assigned = $this->makeEvent($org, 'Assigned');
        $unassigned = $this->makeEvent($org, 'Unassigned');
        $this->assignEvent($assigned, $org, $membership);

        $this->assertTrue($user->can('viewAny', [ReportPolicy::class]));
        $this->assertTrue($user->can('view', [ReportPolicy::class, $assigned]));
        $this->assertFalse($user->can('view', [ReportPolicy::class, $unassigned]));
    }

    public function test_event_from_another_organization_is_denied(): void
    {
        [$user, $org] = $this->ownerWithOrganization();
        $otherOrg = $this->createOrganizationForUser(User::factory()->create());
        $event = $this->makeEvent($otherOrg);

        $this->assertFalse($user->can('view', [ReportPolicy::class, $event]));
    }

    public function test_policy_evaluated_without_active_session_denies(): void
    {
        [$user, $org] = $this->ownerWithOrganization();
        $event = $this->makeEvent($org);

        session()->forget(OrganizationContext::SESSION_KEY);

        $this->assertFalse($user->can('view', [ReportPolicy::class, $event]));
    }

    public function test_user_without_membership_cannot_view_any(): void
    {
        [$user, $org] = $this->ownerWithOrganization();
        $event = $this->makeEvent($org);

        $stranger = User::factory()->create();

        $this->assertFalse($stranger->can('viewAny', [ReportPolicy::class]));
        $this->assertFalse($stranger->can('view', [ReportPolicy::class, $event]));
    }

    /**
     * @return array{0: User, 1: Organization, 2: OrganizationMembership}
     */
    private function memberInOrg(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = $this->createOrganizationForUser($user, [], $role);
        $membership = $organization->memberships()->where('user_id', $user->id)->firstOrFail();

        session([OrganizationContext::SESSION_KEY => $organization->id]);

        return [$user, $organization, $membership];
    }
}
