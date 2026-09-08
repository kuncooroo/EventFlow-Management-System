<?php

namespace Tests\Feature\Install;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CheckHealthCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_a_healthy_environment_and_exits_zero(): void
    {
        $exit = Artisan::call('eventflow:check-health');

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('EventFlow health check', Artisan::output());
    }

    public function test_fails_when_debug_mode_is_enabled_in_production(): void
    {
        config(['app.env' => 'production', 'app.debug' => true]);

        $exit = Artisan::call('eventflow:check-health');

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Debug mode', Artisan::output());
    }

    public function test_succeeds_in_production_when_debug_is_disabled(): void
    {
        config(['app.env' => 'production', 'app.debug' => false]);

        $exit = Artisan::call('eventflow:check-health');

        $this->assertSame(0, $exit);
    }

    public function test_fails_when_the_application_key_is_missing(): void
    {
        config(['app.key' => null]);

        $exit = Artisan::call('eventflow:check-health');

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('APP_KEY', Artisan::output());
    }

    public function test_strict_mode_returns_failure_when_warnings_are_present(): void
    {
        $exit = Artisan::call('eventflow:check-health', ['--strict' => true]);

        $this->assertSame(1, $exit);
    }
}
