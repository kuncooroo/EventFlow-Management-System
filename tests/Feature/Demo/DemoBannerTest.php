<?php

namespace Tests\Feature\Demo;

use App\Support\Demo\DemoMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoBannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DemoMode::flushDemoOrganizationCache();
    }

    public function test_banner_component_renders_when_demo_mode_is_enabled(): void
    {
        config(['demo.enabled' => true]);

        $html = view('components.demo-banner')->render();

        $this->assertStringContainsString('Demo Mode / Changes may be reset periodically.', $html);
    }

    public function test_banner_component_is_invisible_when_demo_mode_is_disabled(): void
    {
        config(['demo.enabled' => false]);

        $html = view('components.demo-banner')->render();

        $this->assertStringNotContainsString('Demo Mode', $html);
    }

    public function test_app_layout_includes_the_demo_banner(): void
    {
        config(['demo.enabled' => true]);

        $html = view('components.layouts.app', ['title' => 'Workspace', 'slot' => ''])->render();

        $this->assertStringContainsString('Demo Mode / Changes may be reset periodically.', $html);
    }

    public function test_public_and_guest_layouts_include_the_demo_banner(): void
    {
        config(['demo.enabled' => true]);

        $public = view('components.layouts.public', ['title' => 'Event', 'slot' => ''])->render();
        $guest = view('components.layouts.guest', ['title' => 'Sign in', 'slot' => ''])->render();

        $this->assertStringContainsString('Demo Mode / Changes may be reset periodically.', $public);
        $this->assertStringContainsString('Demo Mode / Changes may be reset periodically.', $guest);
    }

    public function test_banner_is_absent_from_layouts_when_demo_mode_is_disabled(): void
    {
        config(['demo.enabled' => false]);

        $app = view('components.layouts.app', ['title' => 'Workspace', 'slot' => ''])->render();
        $public = view('components.layouts.public', ['title' => 'Event', 'slot' => ''])->render();

        $this->assertStringNotContainsString('Demo Mode', $app);
        $this->assertStringNotContainsString('Demo Mode', $public);
    }
}
