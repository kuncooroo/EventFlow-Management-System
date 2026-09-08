<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use RuntimeException;
use Tests\TestCase;

class ProductionProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_dev_smoke_routes_are_disabled_in_production(): void
    {
        $this->app->instance('env', 'production');

        $this->get(route('public.foundation'))->assertNotFound();
        $this->get(route('livewire.smoke'))->assertNotFound();
    }

    public function test_production_json_errors_do_not_leak_internal_details(): void
    {
        config(['app.debug' => false]);

        $handler = $this->app->make(Handler::class);
        $request = Request::create('/t/invalid', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $handler->render($request, new RuntimeException('sensitive-internal-detail'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('Server Error', (string) $response->getContent());
        $this->assertStringNotContainsString('sensitive-internal-detail', (string) $response->getContent());
    }

    public function test_production_html_errors_do_not_leak_internal_details(): void
    {
        config(['app.debug' => false]);

        $handler = $this->app->make(Handler::class);
        $request = Request::create('/t/invalid', 'GET');

        $response = $handler->render($request, new RuntimeException('sensitive-internal-detail'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringNotContainsString('sensitive-internal-detail', (string) $response->getContent());
    }
}
