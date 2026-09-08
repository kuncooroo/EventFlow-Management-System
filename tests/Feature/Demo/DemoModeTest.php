<?php

namespace Tests\Feature\Demo;

use App\Support\Demo\DemoMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DemoMode::flushDemoOrganizationCache();
    }

    public function test_enabled_reflects_the_demo_config_flag(): void
    {
        config(['demo.enabled' => true]);
        $this->assertTrue(DemoMode::enabled());

        config(['demo.enabled' => false]);
        $this->assertFalse(DemoMode::enabled());
    }

    public function test_apply_forces_the_log_mail_sink_when_demo_mode_is_enabled(): void
    {
        config(['mail.default' => 'smtp']);
        config(['demo.enabled' => true]);

        DemoMode::apply();

        $this->assertSame('log', config('mail.default'));
    }

    public function test_apply_leaves_mail_config_alone_when_demo_mode_is_disabled(): void
    {
        config(['mail.default' => 'smtp']);
        config(['demo.enabled' => false]);

        DemoMode::apply();

        $this->assertSame('smtp', config('mail.default'));
    }
}
