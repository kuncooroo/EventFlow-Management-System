<?php

namespace Tests\Feature\Reports;

use App\Livewire\Reports\RegistrationReport;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\User;
use App\Policies\ReportPolicy;
use App\Queries\Dashboard\EventDashboardQuery;
use App\Queries\Reports\RegistrationReportQuery;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesReportFixtures;
use Tests\Concerns\InteractsWithOrganizations;
use Tests\TestCase;

class RegistrationReportTest extends TestCase
{
    use CreatesReportFixtures;
    use InteractsWithOrganizations;
    use RefreshDatabase;

    public function test_totals_reconcile_with_seeded_registrations(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $event = $this->makeEvent($org, 'Reports Summit');
        $general = $this->makeType($event, 'General', 100);

        $this->registerAttendee($event, $general, 'confirmed', true);
        $this->registerAttendee($event, $general, 'confirmed');
        $this->registerAttendee($event, $general, 'cancelled');
        $this->registerAttendee($this->makeEvent($org, 'Second Event'), $general, 'confirmed');

        $data = $this->report($org, $owner);

        $this->assertSame(4, $data['total']);
        $this->assertSame(3, $data['confirmed']);
        $this->assertSame(1, $data['cancelled']);
        $this->assertSame(3, $data['by_status']['confirmed']);
        $this->assertSame(1, $data['by_status']['cancelled']);
        $this->assertSame('General', $data['by_ticket_type'][0]['ticket_type']);
        $this->assertSame(4, $data['by_ticket_type'][0]['total']);
        $this->assertSame(3, $data['by_ticket_type'][0]['confirmed']);
    }

    public function test_event_filter_scopes_rows_to_the_selected_event(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $eventA = $this->makeEvent($org, 'Event A');
        $eventB = $this->makeEvent($org, 'Event B');
        $typeA = $this->makeType($eventA, 'General', 100);
        $typeB = $this->makeType($eventB, 'VIP', 50);

        $this->registerAttendee($eventA, $typeA, 'confirmed');
        $this->registerAttendee($eventA, $typeA, 'cancelled');
        $this->registerAttendee($eventB, $typeB, 'confirmed');

        $data = $this->report($org, $owner, ['event_id' => (string) $eventB->id]);

        $this->assertSame(1, $data['total']);
        $this->assertSame(1, $data['confirmed']);
        $this->assertSame('VIP', $data['by_ticket_type'][0]['ticket_type']);
        $this->assertSame($eventB->id, $data['registrations']->first()->event_id);
    }

    public function test_status_and_date_filters_apply(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $event = $this->makeEvent($org);
        $type = $this->makeType($event, 'General', 100);

        $this->registerAttendee($event, $type, 'confirmed', false);
        // Cancelled on a different date.
        $registration = Registration::factory()
            ->for($event)
            ->for($type, 'ticketType')
            ->cancelled()
            ->create(['registered_at' => now()->subDays(10)]);

        $reportData = $this->report($org, $owner, [
            'status' => 'cancelled',
            'registered_from' => now()->subDays(20)->format('Y-m-d'),
            'registered_to' => now()->subDays(5)->format('Y-m-d'),
        ]);

        $this->assertSame(1, $reportData['total']);
        $this->assertSame($registration->id, $reportData['registrations']->first()->id);
    }

    public function test_dashboard_and_report_totals_reconcile(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $event = $this->makeEvent($org);
        $type = $this->makeType($event, 'General', 100);

        $this->registerAttendee($event, $type, 'confirmed', true);
        $this->registerAttendee($event, $type, 'confirmed', true);
        $this->registerAttendee($event, $type, 'cancelled');

        $report = $this->report($org, $owner, ['event_id' => (string) $event->id]);
        $dashboard = app(EventDashboardQuery::class)($event);

        $this->assertSame($dashboard['total_registrations'], $report['total']);
        $this->assertSame($dashboard['confirmed_registrations'], $report['confirmed']);
        $this->assertSame($dashboard['checked_in'], 2);
    }

    public function test_rows_are_paginated(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $event = $this->makeEvent($org);
        $type = $this->makeType($event, 'General', 100);

        foreach (range(1, 16) as $ignored) {
            $this->registerAttendee($event, $type, 'confirmed');
        }

        $pagination = $this->report($org, $owner)['registrations'];

        $this->assertSame(16, $pagination->total());
        $this->assertCount(15, $pagination->items());
    }

    public function test_inaccessible_event_filter_yields_empty_result(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $visible = $this->makeEvent($org, 'Visible');
        $hidden = $this->makeEvent($org, 'Hidden');
        $type = $this->makeType($visible, 'General', 100);
        $hiddenType = $this->makeType($hidden, 'Hidden Type', 100);

        $this->registerAttendee($visible, $type, 'confirmed');
        $this->registerAttendee($hidden, $hiddenType, 'confirmed');

        [$viewer, $membership] = $this->memberWithRole($org, 'viewer');
        $this->assignEvent($visible, $org, $membership);

        $data = app(RegistrationReportQuery::class)($org, $viewer, ['event_id' => (string) $hidden->id]);

        $this->assertSame(0, $data['total']);
        $this->assertTrue($data['registrations']->isEmpty());
    }

    public function test_assigned_viewer_sees_only_assigned_events(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $visible = $this->makeEvent($org, 'Visible');
        $hidden = $this->makeEvent($org, 'Hidden');
        $type = $this->makeType($visible, 'General', 100);
        $hiddenType = $this->makeType($hidden, 'Hidden Type', 100);

        $this->registerAttendee($visible, $type, 'confirmed');
        $this->registerAttendee($hidden, $hiddenType, 'confirmed');

        [$viewer, $membership] = $this->memberWithRole($org, 'viewer');
        $this->assignEvent($visible, $org, $membership);

        $data = app(RegistrationReportQuery::class)($org, $viewer);

        $this->assertSame(1, $data['total']);
        $this->assertSame('Visible', $data['registrations']->first()->event->name);
    }

    public function test_report_page_renders_for_owner(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $event = $this->makeEvent($org);
        $type = $this->makeType($event, 'General', 100);
        $this->registerAttendee($event, $type, 'confirmed', true);

        $this->actingAs($owner)
            ->withSession([OrganizationContext::SESSION_KEY => $org->id])
            ->get(route('app.reports.registrations'))
            ->assertOk()
            ->assertSee('Registration Report')
            ->assertSee('General')
            ->assertSee('Registrations');
    }

    public function test_page_renders_via_livewire_for_member(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();

        Livewire::actingAs($owner)
            ->test(RegistrationReport::class)
            ->assertOk()
            ->assertSee('Registration Report');
    }

    public function test_user_without_an_organization_is_redirected_to_create(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('app.reports.registrations'))
            ->assertRedirect(route('app.organizations.create'));
    }

    public function test_report_policy_guards_the_module(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();

        $this->assertTrue($owner->can('viewAny', [ReportPolicy::class]));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function report(Organization $org, User $user, array $filters = []): array
    {
        return app(RegistrationReportQuery::class)($org, $user, $filters);
    }
}
