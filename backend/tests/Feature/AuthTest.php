<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_succeeds_with_valid_credentials(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $response = $this->postJson('/api/auth/login', ['email' => 'user@example.com', 'password' => 'password']);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'user' => ['id', 'email', 'role']])
            ->assertJsonPath('user.id', $user->id);

        $this->withToken($response->json('access_token'))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'user@example.com');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $this->postJson('/api/auth/login', ['email' => 'user@example.com', 'password' => 'wrong'])
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }

    public function test_login_validates_input(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_logout_invalidates_token(): void
    {
        User::factory()->create(['email' => 'user@example.com']);
        $token = $this->postJson('/api/auth/login', ['email' => 'user@example.com', 'password' => 'password'])->json('access_token');

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
    }
}
