<?php

namespace Tests\Feature\Install;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class InstallCommandTest extends TestCase
{
    use RefreshDatabase;

    private function commandOptions(array $overrides = []): array
    {
        $options = array_merge([
            '--organization' => 'Acme Events',
            '--name' => 'Budi Santoso',
            '--email' => 'owner@acme.test',
            '--password' => 'SuperSecret#123',
            '--no-interaction' => true,
        ], $overrides);

        return array_filter($options, fn ($value): bool => $value !== null);
    }

    public function test_installs_and_bootstraps_the_initial_owner(): void
    {
        $exit = Artisan::call('eventflow:install', $this->commandOptions());

        $this->assertSame(0, $exit);

        $this->assertDatabaseHas('users', ['email' => 'owner@acme.test']);
        $this->assertDatabaseHas('organizations', ['name' => 'Acme Events']);

        $organization = Organization::where('name', 'Acme Events')->first();
        $user = User::where('email', 'owner@acme.test')->first();

        $this->assertDatabaseHas('organization_memberships', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
    }

    public function test_skips_the_owner_bootstrap_with_no_owner(): void
    {
        $exit = Artisan::call('eventflow:install', ['--no-owner' => true, '--no-interaction' => true]);

        $this->assertSame(0, $exit);
        $this->assertSame(0, User::count());
    }

    public function test_skips_the_owner_bootstrap_when_owner_options_are_incomplete(): void
    {
        $exit = Artisan::call('eventflow:install', [
            '--organization' => 'Acme Events',
            '--name' => 'Budi Santoso',
            '--no-interaction' => true,
        ]);

        $this->assertSame(0, $exit);
        $this->assertSame(0, User::count());
        $this->assertStringContainsString('Skipping Owner bootstrap', Artisan::output());
    }

    public function test_fails_preflight_when_the_application_key_is_missing(): void
    {
        config(['app.key' => null]);

        $exit = Artisan::call('eventflow:install', $this->commandOptions());

        $this->assertSame(1, $exit);
        $this->assertSame(0, User::count());
        $this->assertStringContainsString('Preflight checks failed', Artisan::output());
    }
}
