<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

    public function test_login_finds_user_when_database_has_compact_personnummer_without_mutator(): void
    {
        $organization = Organization::factory()->create();

        DB::table('users')->insert([
            'name' => 'Legacy PN user',
            'email' => 'legacy.pn@test.invalid',
            'personnummer' => '8507099805',
            'password' => Hash::make('password'),
            'organization_id' => $organization->id,
            'role' => UserRole::Employee->value,
            'email_verified_at' => now(),
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/v1/login', [
            'personnummer' => '850709-9805',
            'password' => 'password',
            'device_name' => 'test-suite',
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_login_finds_user_when_database_has_twelve_digit_personnummer(): void
    {
        $organization = Organization::factory()->create();

        DB::table('users')->insert([
            'name' => 'Legacy twelve-digit PN',
            'email' => 'legacy.12digit@test.invalid',
            'personnummer' => '198507099805',
            'password' => Hash::make('password'),
            'organization_id' => $organization->id,
            'role' => UserRole::Employee->value,
            'email_verified_at' => now(),
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/v1/login', [
            'personnummer' => '850709-9805',
            'password' => 'password',
            'device_name' => 'test-suite',
        ])->assertOk()->assertJsonPath('success', true);
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
