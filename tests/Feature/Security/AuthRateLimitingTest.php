<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_throttles_after_five_failed_attempts(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertStatus(302);
        }

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_forgot_password_throttles_after_six_requests(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('password.email'), ['email' => 'nobody@example.com'])
                ->assertStatus(302);
        }

        $this->post(route('password.email'), ['email' => 'nobody@example.com'])
            ->assertStatus(429);
    }

    public function test_password_reset_throttles_after_six_requests(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('password.update'), [
                'token' => 'invalid-token',
                'email' => 'nobody@example.com',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])->assertStatus(302);
        }

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => 'nobody@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertStatus(429);
    }

    public function test_public_ticket_page_throttles_per_ip(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->get(route('tickets.public.show', str_repeat('a', 26)))->assertNotFound();
        }

        $this->get(route('tickets.public.show', str_repeat('a', 26)))->assertStatus(429);
    }
}
