<?php

namespace Tests\Feature\Smoke;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use App\Livewire\Smoke\FoundationSmoke;
use Tests\TestCase;

class ApplicationSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_boots_health_endpoint(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_home_page_renders_public_layout(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('EventFlow', false)
            ->assertSee('Event operations in one workspace', false);
    }

    public function test_database_connection_is_available(): void
    {
        $this->assertSame('mysql', config('database.default'));
        $this->assertTrue(DB::connection()->getPdo() instanceof \PDO);
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('sessions'));
        $this->assertTrue(Schema::hasTable('jobs'));
        $this->assertTrue(Schema::hasTable('cache'));
        $this->assertTrue(Schema::hasTable('organizations'));
        $this->assertTrue(Schema::hasTable('organization_memberships'));
    }

    public function test_foundation_smoke_page_renders(): void
    {
        $this->get(route('public.foundation'))
            ->assertOk()
            ->assertSee('Foundation smoke page', false)
            ->assertSee('Livewire', false);
    }

    public function test_livewire_foundation_smoke_component_renders_and_acts(): void
    {
        Livewire::test(FoundationSmoke::class)
            ->assertSee('Foundation smoke check')
            ->call('markAlpineReady')
            ->assertSet('alpineReady', true)
            ->assertSee('Livewire action OK');
    }

    public function test_authenticated_shell_requires_login(): void
    {
        $this->get(route('app.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_login_page_renders_guest_shell(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in to manage your events.', false);
    }
}
