<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TimeEntryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_clock_in_and_out(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $clockIn = $this->postJson('/api/v1/time-entries/clock-in', [
            'latitude' => 55.604981,
            'longitude' => 13.003822,
        ]);

        $clockIn->assertCreated()->assertJsonPath('success', true);

        $clockOut = $this->postJson('/api/v1/time-entries/clock-out', [
            'latitude' => 55.605100,
            'longitude' => 13.003900,
        ]);

        $clockOut->assertOk()->assertJsonPath('success', true);
    }

    public function test_time_entries_cannot_overlap(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/time-entries', [
            'clocked_in_at' => '2026-04-26T08:00:00Z',
            'clocked_out_at' => '2026-04-26T10:00:00Z',
        ])->assertCreated();

        $overlap = $this->postJson('/api/v1/time-entries', [
            'clocked_in_at' => '2026-04-26T09:00:00Z',
            'clocked_out_at' => '2026-04-26T11:00:00Z',
        ]);

        $overlap
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'time_entry_overlap');
    }
}
