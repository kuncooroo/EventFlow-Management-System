<?php

namespace Tests\Feature\Dashboard;

use App\Enums\EventStatus;
use App\Enums\OrganizationRole;
use App\Livewire\Reports\OrganizationDashboard;
use App\Models\CheckIn;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\User;
use App\Queries\Dashboard\OrganizationDashboardQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizationDashboardTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function makeOrgWithMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $membership = $org->memberships()->create([
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => now(),
        ]);
        session(['current_organization_id' => $org->id]);

        return [$user, $org, $membership];
    }

    private function aggregate(Organization $org, User $user): array
    {
        return app(OrganizationDashboardQuery::class)($org, $user);
    }

    private function seedRegistrationsAndCheckIns(Event $event, int $registrations, int $checkedIn): void
    {
        foreach (range(1, $registrations) as $i) {
            $registration = Registration::factory()->for($event)->confirmed()->create();

            if ($i <= $checkedIn) {
                CheckIn::factory()->for($registration)->create();
            }
        }
    }

    // ─── Owner sees org-scoped totals ───────────────────────────────────────

    public function test_owner_sees_org_scoped_totals(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);

        $published = Event::factory()->for($org)->published()->create(['name' => 'Summit']);
        $ongoing = Event::factory()->for($org)->ongoing()->create(['name' => 'Workshop']);
        Event::factory()->for($org)->completed()->create(['name' => 'Past Event']);
        Event::factory()->for($org)->cancelled()->create(['name' => 'Cancelled Event']);
        Event::factory()->for($org)->draft()->create(['name' => 'Draft Event']);

        $this->seedRegistrationsAndCheckIns($published, 2, 1);
        $this->seedRegistrationsAndCheckIns($ongoing, 1, 1);

        $data = $this->aggregate($org, $owner);

        $this->assertSame(5, $data['total_events']);
        $this->assertSame(2, $data['active_events']);
        $this->assertSame(1, $data['upcoming_events']);
        $this->assertSame(1, $data['ongoing_events']);
        $this->assertSame(1, $data['completed_events']);
        $this->assertSame(1, $data['status_counts'][EventStatus::Draft->value]);
        $this->assertSame(1, $data['status_counts'][EventStatus::Cancelled->value]);
        $this->assertSame(3, $data['total_registrations']);
        $this->assertSame(3, $data['confirmed_registrations']);
        $this->assertSame(2, $data['checked_in']);
    }

    // ─── Restricted roles only see assigned events (DASH-001) ───────────────

    public function test_event_manager_sees_only_assigned_events(): void
    {
        [$manager, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::EventManager);

        $assigned = Event::factory()->for($org)->published()->create(['name' => 'Assigned Event']);
        $hidden = Event::factory()->for($org)->ongoing()->create(['name' => 'Hidden Event']);

        $assigned->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        $this->seedRegistrationsAndCheckIns($assigned, 2, 2);
        $this->seedRegistrationsAndCheckIns($hidden, 5, 4);

        $data = $this->aggregate($org, $manager);

        $this->assertSame(1, $data['total_events']);
        $this->assertSame(1, $data['active_events']);
        $this->assertSame(2, $data['total_registrations']);
        $this->assertSame(2, $data['checked_in']);
    }

    public function test_viewer_sees_only_assigned_events(): void
    {
        [$viewer, $org, $membership] = $this->makeOrgWithMember(OrganizationRole::Viewer);

        $assigned = Event::factory()->for($org)->published()->create(['name' => 'Visible Event']);
        $hidden = Event::factory()->for($org)->published()->create(['name' => 'Invisible Event']);

        $assigned->assignments()->create([
            'organization_id' => $org->id,
            'organization_membership_id' => $membership->id,
        ]);

        $data = $this->aggregate($org, $viewer);

        $this->assertSame(1, $data['total_events']);
        $this->assertSame('Visible Event', $data['recent']->first()->name);
    }

    // ─── Org isolation ───────────────────────────────────────────────────────

    public function test_other_organization_events_are_not_counted(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $otherOrg = Organization::factory()->create();
        $otherEvent = Event::factory()->for($otherOrg)->published()->create(['name' => 'Foreign Event']);
        $this->seedRegistrationsAndCheckIns($otherEvent, 4, 3);

        $data = $this->aggregate($org, $owner);

        $this->assertSame(0, $data['total_events']);
        $this->assertSame(0, $data['total_registrations']);
        $this->assertSame(0, $data['checked_in']);
    }

    // ─── Empty org is safe ───────────────────────────────────────────────────

    public function test_empty_organization_renders_safely(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);

        $data = $this->aggregate($org, $owner);

        $this->assertSame(0, $data['total_events']);
        $this->assertSame(0, $data['total_registrations']);
        $this->assertSame(0, $data['checked_in']);

        Livewire::actingAs($owner)
            ->test(OrganizationDashboard::class)
            ->assertOk()
            ->assertSee('0', false)
            ->assertSee('No upcoming events')
            ->assertSee('No events yet');
    }

    // ─── Page rendering ──────────────────────────────────────────────────────

    public function test_dashboard_page_renders_kpis_and_recent_events(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        Event::factory()->for($org)->published()->create(['name' => 'Dashboard Feature Event']);
        $this->seedRegistrationsAndCheckIns(Event::factory()->for($org)->published()->create(), 2, 1);

        $this->actingAs($owner)
            ->get(route('app.dashboard'))
            ->assertOk()
            ->assertSee($org->name)
            ->assertSee('Active Events')
            ->assertSee('Total Registrations')
            ->assertSee('Checked In')
            ->assertSee('Dashboard Feature Event');
    }
}
