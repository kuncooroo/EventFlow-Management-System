<?php

namespace Tests\Feature\Demo;

use App\Actions\Files\DeleteMediaFile;
use App\Actions\Organizations\ChangeMemberRole;
use App\Actions\Organizations\RemoveMember;
use App\Enums\OrganizationRole;
use App\Models\MediaFile;
use App\Models\Organization;
use App\Models\User;
use App\Policies\OrganizationPolicy;
use App\Support\Demo\DemoGate;
use App\Support\Demo\DemoMode;
use Database\Seeders\DemoSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithOrganizations;
use Tests\TestCase;

class DemoRestrictionsTest extends TestCase
{
    use InteractsWithOrganizations;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DemoMode::flushDemoOrganizationCache();
    }

    private function enableDemoMode(): void
    {
        config(['demo.enabled' => true]);

        DemoMode::flushDemoOrganizationCache();
    }

    private function demoOrganization(): Organization
    {
        return Organization::query()->where('slug', DemoMode::demoOrganizationSlug())->firstOrFail();
    }

    public function test_gate_denies_actions_on_the_demo_organization(): void
    {
        $this->enableDemoMode();
        $this->seed(DemoSeeder::class);

        $this->expectException(AuthorizationException::class);

        DemoGate::denyOnDemoOrganization($this->demoOrganization()->id, 'blocked');
    }

    public function test_gate_ignores_non_demo_organizations(): void
    {
        $this->enableDemoMode();
        $organization = Organization::factory()->create();

        DemoGate::denyOnDemoOrganization($organization->id, 'blocked');

        $this->assertTrue(true);
    }

    public function test_gate_is_inactive_when_demo_mode_is_disabled(): void
    {
        config(['demo.enabled' => false]);
        DemoMode::flushDemoOrganizationCache();
        $this->seed(DemoSeeder::class);

        DemoGate::denyOnDemoOrganization($this->demoOrganization()->id, 'blocked');

        $this->assertTrue(true);
    }

    public function test_remove_member_is_blocked_within_the_demo_organization(): void
    {
        $this->enableDemoMode();
        $this->seed(DemoSeeder::class);

        $organization = $this->demoOrganization();
        $owner = User::query()->where('email', DemoSeeder::DEMO_OWNER_EMAIL)->firstOrFail();
        $target = User::query()->where('email', DemoSeeder::DEMO_ADMIN_EMAIL)->firstOrFail()->membershipIn($organization);

        $this->expectException(AuthorizationException::class);

        app(RemoveMember::class)->handle($target, $owner, $organization);
    }

    public function test_remove_member_is_allowed_outside_the_demo_organization(): void
    {
        $this->enableDemoMode();

        $actor = User::factory()->create();
        $organization = $this->createOrganizationForUser($actor, role: OrganizationRole::Owner);
        $membership = $this->addMemberToOrganization($organization, role: OrganizationRole::Staff);

        app(RemoveMember::class)->handle($membership, $actor, $organization);

        $this->assertNotNull($membership->refresh()->removed_at);
    }

    public function test_change_member_role_is_blocked_within_the_demo_organization(): void
    {
        $this->enableDemoMode();
        $this->seed(DemoSeeder::class);

        $organization = $this->demoOrganization();
        $owner = User::query()->where('email', DemoSeeder::DEMO_OWNER_EMAIL)->firstOrFail();
        $target = User::query()->where('email', DemoSeeder::DEMO_ADMIN_EMAIL)->firstOrFail()->membershipIn($organization);

        $this->expectException(AuthorizationException::class);

        app(ChangeMemberRole::class)->handle($target, OrganizationRole::Staff, $owner, $organization);
    }

    public function test_change_member_role_is_allowed_outside_the_demo_organization(): void
    {
        $this->enableDemoMode();

        $actor = User::factory()->create();
        $organization = $this->createOrganizationForUser($actor, role: OrganizationRole::Owner);
        $membership = $this->addMemberToOrganization($organization, role: OrganizationRole::Staff);

        app(ChangeMemberRole::class)->handle($membership, OrganizationRole::Viewer, $actor, $organization);

        $this->assertSame(OrganizationRole::Viewer, $membership->refresh()->role);
    }

    public function test_delete_media_is_blocked_within_the_demo_organization(): void
    {
        $this->enableDemoMode();
        $this->seed(DemoSeeder::class);

        $organization = $this->demoOrganization();
        $owner = User::query()->where('email', DemoSeeder::DEMO_OWNER_EMAIL)->firstOrFail();
        $media = MediaFile::factory()->organizationLogo($organization)->create([
            'uploaded_by_user_id' => $owner->id,
        ]);

        $this->expectException(AuthorizationException::class);

        app(DeleteMediaFile::class)->handle($media, $owner);
    }

    public function test_organization_creation_is_blocked_when_demo_mode_is_enabled(): void
    {
        $user = User::factory()->create();

        $this->enableDemoMode();
        $this->assertFalse((new OrganizationPolicy)->create($user));

        config(['demo.enabled' => false]);
        DemoMode::flushDemoOrganizationCache();
        $this->assertTrue((new OrganizationPolicy)->create($user));
    }

    public function test_organization_store_route_is_forbidden_in_demo_mode(): void
    {
        $this->enableDemoMode();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('app.organizations.store'), ['name' => 'New Company'])
            ->assertForbidden();
    }
}
