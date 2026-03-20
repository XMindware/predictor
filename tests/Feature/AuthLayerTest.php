<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_users_are_redirected_to_the_login_page_for_internal_dashboard_access(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_internal_users_can_log_in_and_log_out_with_the_web_guard(): void
    {
        $user = User::factory()->create();
        $loginToken = 'login-csrf-token';

        $this->withSession(['_token' => $loginToken])
            ->from('/login')
            ->post('/login', [
                '_token' => $loginToken,
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);

        $logoutToken = 'logout-csrf-token';

        $this->withSession(['_token' => $logoutToken])
            ->post('/logout', [
                '_token' => $logoutToken,
            ])
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_external_consumers_can_exchange_credentials_for_an_api_token(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/tokens', [
            'email' => $user->email,
            'password' => 'password',
            'token_name' => 'partner-client',
        ])
            ->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', $user->email);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_protected_api_routes_require_a_valid_sanctum_token(): void
    {
        $this->getJson('/api/user')
            ->assertUnauthorized();

        $user = User::factory()->create();
        $token = $user->createToken('partner-client')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->withToken($token)
            ->getJson('/api/tokens')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'partner-client');
    }
}
