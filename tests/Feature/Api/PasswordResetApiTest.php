<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_returns_generic_success_when_email_unknown(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/forgot-password', [
            'email' => 'finns-inte@example.com',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        Notification::assertNothingSent();
    }

    public function test_forgot_password_sends_email_notification_for_known_user(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'kand@example.com',
        ]);

        $this->postJson('/api/v1/forgot-password', [
            'email' => $user->email,
        ])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_updates_password_and_allows_login(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset.target@example.com',
            'personnummer' => '850709-9805',
            'password' => Hash::make('old-password'),
        ]);

        $this->postJson('/api/v1/forgot-password', [
            'email' => $user->email,
        ])->assertOk();

        $token = null;

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $this->assertNotNull($token);

        $this->postJson('/api/v1/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));

        $this->postJson('/api/v1/login', [
            'personnummer' => $user->personnummer,
            'password' => 'new-secure-password',
            'device_name' => 'test-suite',
        ])->assertOk();
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'invalid.token@example.com',
        ]);

        $this->postJson('/api/v1/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'new-password-xyz',
            'password_confirmation' => 'new-password-xyz',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'invalid_reset_token');
    }
}
