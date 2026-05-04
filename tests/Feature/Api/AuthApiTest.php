<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_personnummer(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'API User',
            'email' => 'api.user@example.com',
            'personnummer' => '850709-9805',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'api.user@example.com');
    }

    public function test_login_returns_token_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'personnummer' => '850709-9805',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'personnummer' => $user->personnummer,
            'password' => 'password',
            'device_name' => 'test-suite',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer');
    }

    public function test_login_accepts_personnummer_without_dash_matching_stored_canonical(): void
    {
        $user = User::factory()->create([
            'personnummer' => '850709-9805',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'personnummer' => '8507099805',
            'password' => 'password',
            'device_name' => 'test-suite',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame($user->id, $response->json('data.user.id'));
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/logout');

        $response
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
