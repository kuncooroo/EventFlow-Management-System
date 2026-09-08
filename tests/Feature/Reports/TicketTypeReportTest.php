<?php

namespace Tests\Feature\Reports;

use App\Livewire\Reports\TicketTypeReport;
use App\Models\Organization;
use App\Models\User;
use App\Queries\Reports\TicketTypeReportQuery;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesReportFixtures;
use Tests\Concerns\InteractsWithOrganizations;
use Tests\TestCase;

class TicketTypeReportTest extends TestCase
{
    use CreatesReportFixtures;
    use InteractsWithOrganizations;
    use RefreshDatabase;

    public function test_type_totals_reconcile_with_seeded_data(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $event = $this->makeEvent($org, 'Reports Summit');
        $general = $this->makeType($event, 'General', 100);
        $vip = $this->makeType($event, 'VIP', 20);

        $this->registerAttendee($event, $general, 'confirmed', true);
        $this->registerAttendee($event, $general, 'confirmed');
        $this->registerAttendee($event, $general, 'cancelled');
        $this->registerAttendee($event, $vip, 'confirmed', true);

        $data = $this->report($org, $owner);

        $types = collect($data['ticket_types'])->keyBy('name');

        $this->assertSame(3, $types['General']['registrations']);
        $this->assertSame(2, $types['General']['confirmed']);
        $this->assertSame(1, $types['General']['cancelled']);
        $this->assertSame(1, $types['General']['checked_in']);
        $this->assertSame(100, $types['General']['capacity']);

        $this->assertSame(1, $types['VIP']['registrations']);
        $this->assertSame(1, $types['VIP']['confirmed']);
        $this->assertSame(1, $types['VIP']['checked_in']);

        $this->assertSame(4, $data['totals']['registrations']);
        $this->assertSame(3, $data['totals']['confirmed']);
        $this->assertSame(2, $data['totals']['checked_in']);
    }

    public function test_zero_registration_ticket_type_is_still_listed(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $event = $this->makeEvent($org);
        $this->makeType($event, 'Empty Type', 50);

        $types = collect($this->report($org, $owner)['ticket_types'])->keyBy('name');

        $this->assertSame(0, $types['Empty Type']['registrations']);
        $this->assertSame(0, $types['Empty Type']['confirmed']);
    }

    public function test_event_filter_limits_to_selected_event(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $eventA = $this->makeEvent($org, 'Event A');
        $eventB = $this->makeEvent($org, 'Event B');
        $typeA = $this->makeType($eventA, 'General', 100);
        $typeB = $this->makeType($eventB, 'VIP', 50);

        $this->registerAttendee($eventA, $typeA, 'confirmed');
        $this->registerAttendee($eventB, $typeB, 'confirmed', true);

        $types = $this->report($org, $owner, ['event_id' => (string) $eventB->id])['ticket_types'];

        $this->assertCount(1, $types);
        $this->assertSame('VIP', $types[0]['name']);
    }

    public function test_viewer_sees_only_assigned_event_ticket_types(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $visible = $this->makeEvent($org, 'Visible');
        $hidden = $this->makeEvent($org, 'Hidden');
        $visibleType = $this->makeType($visible, 'General', 100);
        $hiddenType = $this->makeType($hidden, 'VIP', 50);

        $this->registerAttendee($visible, $visibleType, 'confirmed', true);
        $this->registerAttendee($hidden, $hiddenType, 'confirmed');

        [$viewer, $membership] = $this->memberWithRole($org, 'viewer');
        $this->assignEvent($visible, $org, $membership);

        $types = $this->report($org, $viewer)['ticket_types'];

        $this->assertCount(1, $types);
        $this->assertSame('General', $types[0]['name']);
        $this->assertSame(1, $types[0]['checked_in']);
        $this->assertSame(1, $this->report($org, $viewer)['totals']['checked_in']);
        $this->assertSame(1, $this->report($org, $viewer)['totals']['confirmed']);
    }

    public function test_inaccessible_event_filter_yields_empty_result(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $visible = $this->makeEvent($org, 'Visible');
        $hidden = $this->makeEvent($org, 'Hidden');
        $visibleType = $this->makeType($visible, 'General', 100);
        $hiddenType = $this->makeType($hidden, 'VIP', 50);
        $this->registerAttendee($visible, $visibleType, 'confirmed');
        $this->registerAttendee($hidden, $hiddenType, 'confirmed');

        [$viewer, $membership] = $this->memberWithRole($org, 'viewer');
        $this->assignEvent($visible, $org, $membership);

        $data = app(TicketTypeReportQuery::class)($org, $viewer, ['event_id' => (string) $hidden->id]);

        $this->assertSame([], $data['ticket_types']);
        $this->assertSame(0, $data['totals']['registrations']);
    }

    public function test_report_page_renders_for_owner(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $event = $this->makeEvent($org);
        $this->makeType($event, 'General', 100);

        $this->actingAs($owner)
            ->withSession([OrganizationContext::SESSION_KEY => $org->id])
            ->get(route('app.reports.ticket-types'))
            ->assertOk()
            ->assertSee('Ticket Type Report')
            ->assertSee('General');
    }

    public function test_page_renders_via_livewire_for_member(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();

        Livewire::actingAs($owner)
            ->test(TicketTypeReport::class)
            ->assertOk()
            ->assertSee('Ticket Type Report');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function report(Organization $org, ?User $user, array $filters = []): array
    {
        return app(TicketTypeReportQuery::class)($org, $user, $filters);
    }
}
