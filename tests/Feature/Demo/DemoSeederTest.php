<?php

namespace Tests\Feature\Demo;

use App\Enums\OrganizationRole;
use App\Models\CheckIn;
use App\Models\Event;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\User;
use App\Support\Demo\DemoMode;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DemoMode::flushDemoOrganizationCache();
    }

    public function test_it_seeds_the_demo_organization_and_team(): void
    {
        $this->seed(DemoSeeder::class);

        $organization = Organization::query()->where('slug', DemoMode::demoOrganizationSlug())->firstOrFail();
        $this->assertSame('EventFlow Demo', $organization->name);

        foreach (DemoSeeder::DEMO_USER_EMAILS as $email) {
            $this->assertNotNull(User::query()->where('email', $email)->first());
        }

        $roles = $organization->activeMemberships()
            ->with('user')
            ->get()
            ->map(fn (OrganizationMembership $membership): array => [
                $membership->user->email,
                $membership->role->value,
            ])
            ->all();

        $this->assertContains([DemoSeeder::DEMO_OWNER_EMAIL, OrganizationRole::Owner->value], $roles);
        $this->assertContains([DemoSeeder::DEMO_ADMIN_EMAIL, OrganizationRole::Admin->value], $roles);
        $this->assertContains([DemoSeeder::DEMO_STAFF_EMAIL, OrganizationRole::Staff->value], $roles);
    }

    public function test_it_seeds_synthetic_events_with_registration_and_check_in_history(): void
    {
        $this->seed(DemoSeeder::class);

        $organization = Organization::query()->where('slug', DemoMode::demoOrganizationSlug())->firstOrFail();
        $events = $organization->events;

        $this->assertGreaterThanOrEqual(4, $events->count());

        foreach ([
            'technology-conference-2026',
            'digital-marketing-workshop',
            'campus-career-fair',
            'spring-product-expo',
        ] as $slug) {
            $this->assertNotNull($events->firstWhere('public_slug', $slug), "Missing demo event {$slug}");
        }

        $eventIds = $events->pluck('id');
        $registrationIds = Registration::query()->whereIn('event_id', $eventIds)->pluck('id');

        $this->assertGreaterThan(0, $registrationIds->count());
        $this->assertGreaterThan(0, Ticket::query()->whereIn('registration_id', $registrationIds)->count());
        $this->assertGreaterThan(0, CheckIn::query()->whereIn('registration_id', $registrationIds)->count());
    }

    public function test_seeded_demo_owner_can_sign_in(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertTrue(Auth::attempt([
            'email' => DemoSeeder::DEMO_OWNER_EMAIL,
            'password' => DemoSeeder::DEMO_PASSWORD,
        ]));
    }

    public function test_seeding_is_idempotent(): void
    {
        $this->seed(DemoSeeder::class);

        $organizationCount = Organization::count();
        $eventCount = Event::count();
        $registrationCount = Registration::count();

        $this->seed(DemoSeeder::class);

        $this->assertSame($organizationCount, Organization::count());
        $this->assertSame($eventCount, Event::count());
        $this->assertSame($registrationCount, Registration::count());
    }
}
