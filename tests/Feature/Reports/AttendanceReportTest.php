<?php

namespace Tests\Feature\Reports;

use App\Livewire\Reports\AttendanceReport;
use App\Models\Organization;
use App\Models\User;
use App\Queries\Dashboard\EventDashboardQuery;
use App\Queries\Reports\AttendanceReportQuery;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesReportFixtures;
use Tests\Concerns\InteractsWithOrganizations;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    use CreatesReportFixtures;
    use InteractsWithOrganizations;
    use RefreshDatabase;

    public function test_overall_totals_reconcile_across_events(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();

        $eventA = $this->makeEvent($org, 'Event A');
        $typeA = $this->makeType($eventA, 'General', 100);
        $this->registerAttendee($eventA, $typeA, 'confirmed', true);
        $this->registerAttendee($eventA, $typeA, 'confirmed');
        $this->registerAttendee($eventA, $typeA, 'cancelled');

        $eventB = $this->makeEvent($org, 'Event B');
        $typeB = $this->makeType($eventB, 'VIP', 50);
        $this->registerAttendee($eventB, $typeB, 'confirmed', true);
        $this->registerAttendee($eventB, $typeB, 'confirmed', true);

        $data = $this->report($org, $owner);

        $this->assertSame(5, $data['total_registrations']);
        $this->assertSame(4, $data['total_confirmed']);
        $this->assertSame(3, $data['total_checked_in']);
        $this->assertSame(75, $data['attendance_percentage']);

        $rows = collect($data['rows'])->keyBy('event');

        $this->assertSame(2, $rows['Event A']['confirmed']);
        $this->assertSame(1, $rows['Event A']['checked_in']);
        $this->assertSame(50, $rows['Event A']['attendance_percentage']);

        $this->assertSame(2, $rows['Event B']['checked_in']);
        $this->assertSame(100, $rows['Event B']['attendance_percentage']);
    }

    public function test_overall_totals_reconcile_with_event_dashboards(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();

        $event = $this->makeEvent($org);
        $type = $this->makeType($event, 'General', 100);
        $this->registerAttendee($event, $type, 'confirmed', true);
        $this->registerAttendee($event, $type, 'confirmed');
        $this->registerAttendee($event, $type, 'cancelled');

        $report = $this->report($org, $owner, ['event_id' => (string) $event->id]);
        $dashboard = app(EventDashboardQuery::class)($event);

        $this->assertSame($dashboard['total_registrations'], $report['total_registrations']);
        $this->assertSame($dashboard['confirmed_registrations'], $report['total_confirmed']);
        $this->assertSame($dashboard['checked_in'], $report['total_checked_in']);
        $this->assertSame($dashboard['attendance_percentage'], $report['attendance_percentage']);
    }

    public function test_event_filter_breaks_down_by_ticket_type(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();

        $event = $this->makeEvent($org, 'Event A');
        $general = $this->makeType($event, 'General', 100);
        $vip = $this->makeType($event, 'VIP', 20);
        $this->registerAttendee($event, $general, 'confirmed', true);
        $this->registerAttendee($event, $general, 'confirmed');
        $this->registerAttendee($event, $vip, 'cancelled');

        $rows = collect($this->report($org, $owner, ['event_id' => (string) $event->id])['rows'])->keyBy('ticket_type');

        $this->assertSame('General', $rows['General']['ticket_type']);
        $this->assertSame(2, $rows['General']['confirmed']);
        $this->assertSame(1, $rows['General']['checked_in']);
        $this->assertSame(50, $rows['General']['attendance_percentage']);
        $this->assertSame(0, $rows['VIP']['confirmed']);
        $this->assertNull($rows['VIP']['attendance_percentage']);
    }

    public function test_zero_attendee_event_shows_not_available(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $event = $this->makeEvent($org);
        $type = $this->makeType($event, 'General', 100);
        $this->registerAttendee($event, $type, 'cancelled');

        $data = $this->report($org, $owner);

        $this->assertSame(0, $data['total_confirmed']);
        $this->assertNull($data['attendance_percentage']);

        $row = $data['rows'][0];
        $this->assertSame(0, $row['confirmed']);
        $this->assertNull($row['attendance_percentage']);
    }

    public function test_viewer_sees_only_assigned_events(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $visible = $this->makeEvent($org, 'Visible');
        $hidden = $this->makeEvent($org, 'Hidden');
        $visibleType = $this->makeType($visible, 'General', 100);
        $hiddenType = $this->makeType($hidden, 'VIP', 50);
        $this->registerAttendee($visible, $visibleType, 'confirmed', true);
        $this->registerAttendee($hidden, $hiddenType, 'confirmed', true);

        [$viewer, $membership] = $this->memberWithRole($org, 'viewer');
        $this->assignEvent($visible, $org, $membership);

        $data = $this->report($org, $viewer);

        $this->assertCount(1, $data['rows']);
        $this->assertSame('Visible', $data['rows'][0]['event']);
        $this->assertSame(1, $data['total_checked_in']);
    }

    public function test_inaccessible_event_filter_yields_empty_result(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $visible = $this->makeEvent($org, 'Visible');
        $hidden = $this->makeEvent($org, 'Hidden');
        $visibleType = $this->makeType($visible, 'General', 100);
        $hiddenType = $this->makeType($hidden, 'VIP', 50);
        $this->registerAttendee($visible, $visibleType, 'confirmed');
        $this->registerAttendee($hidden, $hiddenType, 'confirmed', true);

        [$viewer, $membership] = $this->memberWithRole($org, 'viewer');
        $this->assignEvent($visible, $org, $membership);

        $data = app(AttendanceReportQuery::class)($org, $viewer, ['event_id' => (string) $hidden->id]);

        $this->assertSame([], $data['rows']);
        $this->assertSame(0, $data['total_confirmed']);
        $this->assertNull($data['attendance_percentage']);
    }

    public function test_report_page_renders_for_owner(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $event = $this->makeEvent($org);
        $type = $this->makeType($event, 'General', 100);
        $this->registerAttendee($event, $type, 'confirmed', true);

        $this->actingAs($owner)
            ->withSession([OrganizationContext::SESSION_KEY => $org->id])
            ->get(route('app.reports.attendance'))
            ->assertOk()
            ->assertSee('Attendance Report')
            ->assertSee('Attendance');
    }

    public function test_page_renders_via_livewire_for_member(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();

        Livewire::actingAs($owner)
            ->test(AttendanceReport::class)
            ->assertOk()
            ->assertSee('Attendance Report');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function report(Organization $org, ?User $user, array $filters = []): array
    {
        return app(AttendanceReportQuery::class)($org, $user, $filters);
    }
}
