<?php

namespace Tests\Feature\Reports;

use App\Enums\ExportType;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use App\Queries\Reports\AttendanceReportQuery;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesReportFixtures;
use Tests\Concerns\InteractsWithOrganizations;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use CreatesReportFixtures;
    use InteractsWithOrganizations;
    use RefreshDatabase;

    public function test_registration_export_returns_csv_with_rows(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $event = $this->makeEvent($org, 'Expo');
        $type = $this->makeType($event, 'General', 100);
        $this->registerAttendee($event, $type, 'confirmed', true);
        $this->registerAttendee($event, $type, 'cancelled');

        $response = $this->actingAs($owner)
            ->withSession([OrganizationContext::SESSION_KEY => $org->id])
            ->get(route('app.reports.export', ['type' => 'registrations']))
            ->assertOk()
            ->assertDownload();

        $content = $this->stripBom($response->streamedContent());

        $this->assertStringContainsString('Registration Code', $content);

        $rows = $this->csvRows($content);
        $this->assertCount(3, $rows); // header + 2 rows
        $this->assertSame('Confirmed', $rows[1][4]);
    }

    public function test_export_never_contains_ticket_qr_secrets(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $event = $this->makeEvent($org);
        $type = $this->makeType($event, 'General', 100);
        $registration = $this->registerAttendee($event, $type, 'confirmed');

        Ticket::factory()->for($registration)->create([
            'ticket_code' => 'TICKETCODE123',
            'qr_token' => 'super-secret-qr-token',
        ]);

        $content = $this->actingAs($owner)
            ->withSession([OrganizationContext::SESSION_KEY => $org->id])
            ->get(route('app.reports.export', ['type' => 'registrations']))
            ->assertOk()
            ->streamedContent();

        $this->assertStringNotContainsString('super-secret-qr-token', $content);
        $this->assertStringContainsString('TICKETCODE123', $content);
    }

    public function test_registration_export_respects_status_filter(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $event = $this->makeEvent($org);
        $type = $this->makeType($event, 'General', 100);
        $this->registerAttendee($event, $type, 'confirmed', true);
        $this->registerAttendee($event, $type, 'confirmed');
        $this->registerAttendee($event, $type, 'cancelled');

        $content = $this->actingAs($owner)
            ->withSession([OrganizationContext::SESSION_KEY => $org->id])
            ->get(route('app.reports.export', ['type' => 'registrations', 'status' => 'cancelled']))
            ->assertOk()
            ->streamedContent();

        $rows = $this->csvRows($this->stripBom($content));
        $this->assertCount(2, $rows); // header + 1 cancelled row
        $this->assertSame('Cancelled', $rows[1][4]);
    }

    public function test_registration_export_respects_event_filter(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $eventA = $this->makeEvent($org, 'Event A');
        $eventB = $this->makeEvent($org, 'Event B');
        $typeA = $this->makeType($eventA, 'General', 100);
        $typeB = $this->makeType($eventB, 'VIP', 50);
        $this->registerAttendee($eventA, $typeA, 'confirmed');
        $this->registerAttendee($eventB, $typeB, 'confirmed');

        $content = $this->actingAs($owner)
            ->withSession([OrganizationContext::SESSION_KEY => $org->id])
            ->get(route('app.reports.export', ['type' => 'registrations', 'event_id' => $eventB->id]))
            ->assertOk()
            ->streamedContent();

        $rows = $this->csvRows($this->stripBom($content));
        $this->assertCount(2, $rows);
        $this->assertSame('Event B', $rows[1][1]);
        $this->assertSame('VIP', $rows[1][2]);
    }

    public function test_attendees_export_requires_an_accessible_event(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $visible = $this->makeEvent($org, 'Visible');
        $hidden = $this->makeEvent($org, 'Hidden');
        $type = $this->makeType($visible, 'General', 100);
        $this->registerAttendee($visible, $type, 'confirmed', true);

        [$viewer, $membership] = $this->memberWithRole($org, 'viewer');
        $this->assignEvent($visible, $org, $membership);

        $this->actingAs($viewer)
            ->withSession([OrganizationContext::SESSION_KEY => $org->id])
            ->get(route('app.reports.export', ['type' => 'attendees', 'event_id' => $hidden->id]))
            ->assertNotFound();

        $this->actingAs($viewer)
            ->withSession([OrganizationContext::SESSION_KEY => $org->id])
            ->get(route('app.reports.export', ['type' => 'attendees', 'event_id' => $visible->id]))
            ->assertOk();
    }

    public function test_attendees_export_denies_cross_organization_events(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $strangerOrg = $this->createOrganizationForUser(User::factory()->create());
        $foreignEvent = $this->makeEvent($strangerOrg, 'Foreign');

        $this->actingAs($owner)
            ->withSession([OrganizationContext::SESSION_KEY => $org->id])
            ->get(route('app.reports.export', ['type' => 'attendees', 'event_id' => $foreignEvent->id]))
            ->assertNotFound();
    }

    public function test_attendees_export_respects_checked_in_filter(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $event = $this->makeEvent($org);
        $type = $this->makeType($event, 'General', 100);
        $this->registerAttendee($event, $type, 'confirmed', true);
        $this->registerAttendee($event, $type, 'confirmed');

        $content = $this->actingAs($owner)
            ->withSession([OrganizationContext::SESSION_KEY => $org->id])
            ->get(route('app.reports.export', ['type' => 'attendees', 'event_id' => $event->id, 'checked_in' => '1']))
            ->assertOk()
            ->streamedContent();

        $rows = $this->csvRows($this->stripBom($content));
        $this->assertCount(2, $rows);
        $this->assertSame('Yes', $rows[1][5]);
    }

    public function test_attendance_export_rows_match_attendance_report(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $event = $this->makeEvent($org, 'Summit');
        $type = $this->makeType($event, 'General', 100);
        $this->registerAttendee($event, $type, 'confirmed', true);
        $this->registerAttendee($event, $type, 'confirmed');
        $this->registerAttendee($event, $type, 'cancelled');

        $rows = $this->csvRows($this->stripBom(
            $this->actingAs($owner)
                ->withSession([OrganizationContext::SESSION_KEY => $org->id])
                ->get(route('app.reports.export', ['type' => 'attendance']))
                ->assertOk()
                ->streamedContent(),
        ));

        $this->assertSame(['Event', 'Ticket Type', 'Registrations', 'Confirmed', 'Checked In', 'Attendance'], $rows[0]);

        $report = app(AttendanceReportQuery::class)($org, $owner)['rows'];
        $this->assertCount(1, $report);

        $this->assertSame('Summit', $rows[1][0]);
        $this->assertSame('3', $rows[1][2]);
        $this->assertSame('2', $rows[1][3]);
        $this->assertSame('1', $rows[1][4]);
        $this->assertSame('50%', $rows[1][5]);
    }

    public function test_csv_escapes_special_characters(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $event = $this->makeEvent($org);
        $type = $this->makeType($event, 'General', 100);
        $registration = $this->registerAttendee($event, $type, 'confirmed');

        $registration->update(['attendee_name' => "Smith, John \"JD\"\nLine Two"]);

        $content = $this->actingAs($owner)
            ->withSession([OrganizationContext::SESSION_KEY => $org->id])
            ->get(route('app.reports.export', ['type' => 'registrations']))
            ->assertOk()
            ->streamedContent();

        $rows = $this->csvRows($this->stripBom($content));
        $this->assertSame("Smith, John \"JD\"\nLine Two", $rows[1][6]);
    }

    public function test_empty_export_still_contains_a_header_row(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $this->makeEvent($org);

        $content = $this->actingAs($owner)
            ->withSession([OrganizationContext::SESSION_KEY => $org->id])
            ->get(route('app.reports.export', ['type' => 'registrations']))
            ->assertOk()
            ->streamedContent();

        $rows = $this->csvRows($this->stripBom($content));
        $this->assertCount(1, $rows);
        $this->assertSame('Registration Code', $rows[0][0]);
    }

    public function test_invalid_export_type_is_rejected(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();

        $this->actingAs($owner)
            ->withSession([OrganizationContext::SESSION_KEY => $org->id])
            ->getJson(route('app.reports.export', ['type' => 'xlsx']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_attendees_export_without_event_is_rejected(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();

        $this->actingAs($owner)
            ->withSession([OrganizationContext::SESSION_KEY => $org->id])
            ->getJson(route('app.reports.export', ['type' => ExportType::Attendees->value]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['event_id']);
    }

    public function test_user_without_an_organization_is_redirected_away(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('app.reports.export', ['type' => 'registrations']))
            ->assertRedirect(route('app.organizations.create'));
    }

    public function test_large_export_streams_without_exhausting_memory(): void
    {
        [$owner, $org] = $this->ownerWithOrganization();
        $event = $this->makeEvent($org);
        $this->bulkInsertRegistrations($event, 10_005);

        $before = memory_get_peak_usage(true);

        $response = $this->actingAs($owner)
            ->withSession([OrganizationContext::SESSION_KEY => $org->id])
            ->get(route('app.reports.export', ['type' => 'registrations']))
            ->assertOk();

        $content = $response->streamedContent();

        $this->assertLessThan(64 * 1024 * 1024, memory_get_peak_usage(true) - $before);

        $lines = array_filter(explode("\n", $this->stripBom($content)), fn (string $line): bool => $line !== '');
        $this->assertCount(10_006, $lines); // header + 10_005 registrations
    }

    /**
     * @return list<list<string>>
     */
    private function csvRows(string $content): array
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function stripBom(string $content): string
    {
        return ltrim($content, "\xEF\xBB\xBF");
    }

    private function bulkInsertRegistrations(Event $event, int $count): void
    {
        $now = now();
        $buffer = [];

        foreach (range(1, $count) as $ignored) {
            $buffer[] = [
                'event_id' => $event->id,
                'ticket_type_id' => null,
                'cancelled_by_user_id' => null,
                'registration_code' => (string) Str::ulid(),
                'status' => 'confirmed',
                'attendee_name' => 'Smoke Attendee',
                'attendee_email' => 'smoke@example.com',
                'attendee_phone' => null,
                'attendee_organization' => null,
                'registered_at' => $now,
                'cancelled_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($buffer) === 500) {
                DB::table('registrations')->insert($buffer);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            DB::table('registrations')->insert($buffer);
        }
    }
}
