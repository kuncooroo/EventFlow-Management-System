<?php

namespace Tests\Feature\Demo;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\User;
use App\Support\Demo\DemoMode;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoResetCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DemoMode::flushDemoOrganizationCache();
    }

    public function test_reset_rebuilds_a_fresh_demo_baseline(): void
    {
        $this->seed(DemoSeeder::class);

        $organization = Organization::query()->where('slug', DemoMode::demoOrganizationSlug())->firstOrFail();
        $previousId = $organization->id;
        $previousEventIds = $organization->events()->pluck('id')->all();
        $previousOwnerId = User::query()->where('email', DemoSeeder::DEMO_OWNER_EMAIL)->value('id');

        $this->artisan('eventflow:demo:reset')->assertSuccessful();

        $fresh = Organization::query()->where('slug', DemoMode::demoOrganizationSlug())->firstOrFail();

        $this->assertNotSame($previousId, $fresh->id);
        $this->assertEmpty(Event::query()->where('organization_id', $previousId)->pluck('id'));
        $this->assertNotSame($previousEventIds, $fresh->events()->pluck('id')->all());
        $this->assertNotSame(
            $previousOwnerId,
            User::query()->where('email', DemoSeeder::DEMO_OWNER_EMAIL)->value('id'),
        );

        $this->assertSame(3, $fresh->activeMemberships()->count());
        $this->assertGreaterThanOrEqual(4, $fresh->events()->count());
        $this->assertGreaterThan(
            0,
            Registration::query()->whereIn('event_id', $fresh->events()->pluck('id'))->count(),
        );
    }

    public function test_reset_seeds_a_demo_baseline_when_none_exists(): void
    {
        $this->artisan('eventflow:demo:reset')->assertSuccessful();

        $organization = Organization::query()->where('slug', DemoMode::demoOrganizationSlug())->firstOrFail();

        $this->assertGreaterThanOrEqual(4, $organization->events()->count());
    }

    public function test_reset_leaves_non_demo_data_untouched(): void
    {
        $keptOrganization = Organization::factory()->create(['name' => 'Keep Me']);
        Event::factory()->create(['organization_id' => $keptOrganization->id]);

        $this->seed(DemoSeeder::class);

        $this->artisan('eventflow:demo:reset')->assertSuccessful();

        $this->assertSame(1, Event::query()->where('organization_id', $keptOrganization->id)->count());
        $this->assertSame(1, Organization::whereKey($keptOrganization->id)->count());
    }
}
