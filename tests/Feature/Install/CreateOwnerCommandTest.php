<?php

namespace Tests\Feature\Install;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateOwnerCommandTest extends TestCase
{
    use RefreshDatabase;

    private function commandOptions(array $overrides = []): array
    {
        $options = array_merge([
            '--organization' => 'Acme Events',
            '--name' => 'Budi Santoso',
            '--email' => 'Owner@Acme.test',
            '--password' => 'SuperSecret#123',
            '--no-interaction' => true,
        ], $overrides);

        return array_filter($options, fn ($value): bool => $value !== null);
    }

    public function test_bootstraps_owner_user_organization_and_membership(): void
    {
        $exit = Artisan::call('eventflow:create-owner', $this->commandOptions());

        $this->assertSame(0, $exit);

        $this->assertDatabaseHas('users', ['email' => 'owner@acme.test']);
        $user = User::where('email', 'owner@acme.test')->first();
        $this->assertSame('Budi Santoso', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('SuperSecret#123', $user->password));

        $this->assertDatabaseHas('organizations', ['name' => 'Acme Events', 'slug' => 'acme-events']);
        $organization = Organization::where('name', 'Acme Events')->first();
        $this->assertSame('UTC', $organization->timezone);

        $this->assertDatabaseHas('organization_memberships', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner->value,
        ]);
    }

    public function test_never_echoes_the_password(): void
    {
        $exit = Artisan::call('eventflow:create-owner', $this->commandOptions());

        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringNotContainsString('SuperSecret#123', $output);
        $this->assertStringContainsString('Owner user:', $output);
    }

    public function test_is_idempotent_when_rerun(): void
    {
        Artisan::call('eventflow:create-owner', $this->commandOptions());
        $exit = Artisan::call('eventflow:create-owner', $this->commandOptions());

        $this->assertSame(0, $exit);
        $this->assertSame(1, User::count());
        $this->assertSame(1, Organization::count());
        $this->assertSame(1, OrganizationMembership::count());
    }

    public function test_reuses_an_existing_organization_by_name(): void
    {
        Organization::factory()->create(['name' => 'Acme Events']);

        $exit = Artisan::call('eventflow:create-owner', $this->commandOptions());

        $this->assertSame(0, $exit);
        $this->assertSame(1, Organization::count());
        $this->assertDatabaseHas('organization_memberships', ['role' => OrganizationRole::Owner->value]);
    }

    public function test_reuses_an_existing_user_and_resets_the_password(): void
    {
        User::factory()->create([
            'email' => 'owner@acme.test',
            'name' => 'Old Name',
            'password' => 'OldPassword#123',
        ]);

        $exit = Artisan::call('eventflow:create-owner', $this->commandOptions());

        $this->assertSame(0, $exit);
        $this->assertSame(1, User::count());

        $user = User::where('email', 'owner@acme.test')->first();
        $this->assertSame('Budi Santoso', $user->name);
        $this->assertTrue(Hash::check('SuperSecret#123', $user->password));
        $this->assertFalse(Hash::check('OldPassword#123', $user->password));
    }

    public function test_requires_a_password_for_a_new_user_in_non_interactive_mode(): void
    {
        $exit = Artisan::call('eventflow:create-owner', $this->commandOptions(['--password' => null]));

        $this->assertSame(1, $exit);
        $this->assertSame(0, User::count());
        $this->assertSame(0, Organization::count());
    }

    public function test_rejects_an_invalid_email(): void
    {
        $exit = Artisan::call('eventflow:create-owner', $this->commandOptions(['--email' => 'not-an-email']));

        $this->assertSame(1, $exit);
        $this->assertSame(0, User::count());
        $this->assertSame(0, Organization::count());
    }

    public function test_rejects_a_short_password(): void
    {
        $exit = Artisan::call('eventflow:create-owner', $this->commandOptions(['--password' => 'abc']));

        $this->assertSame(1, $exit);
        $this->assertSame(0, User::count());
        $this->assertSame(0, Organization::count());
    }

    public function test_creates_a_unique_slug_when_the_base_slug_is_taken(): void
    {
        Organization::factory()->create(['name' => 'Other Org', 'slug' => 'acme-events']);

        $exit = Artisan::call('eventflow:create-owner', $this->commandOptions());

        $this->assertSame(0, $exit);
        $this->assertDatabaseHas('organizations', ['name' => 'Acme Events', 'slug' => 'acme-events-2']);
    }

    public function test_upgrades_an_existing_staff_membership_to_owner(): void
    {
        $organization = Organization::factory()->create(['name' => 'Acme Events']);
        $user = User::factory()->create(['email' => 'owner@acme.test']);
        OrganizationMembership::factory()->for($organization)->for($user)->create(['role' => OrganizationRole::Staff]);

        $exit = Artisan::call('eventflow:create-owner', $this->commandOptions());

        $this->assertSame(0, $exit);
        $this->assertSame(1, OrganizationMembership::count());
        $this->assertSame(OrganizationRole::Owner, $organization->activeMemberships()->first()->role);
    }
}
