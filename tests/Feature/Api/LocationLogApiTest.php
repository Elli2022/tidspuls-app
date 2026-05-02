<?php

namespace Tests\Feature\Api;

use App\Models\LocationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LocationLogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_store_location_log(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/location-logs', [
            'latitude' => 59.3293,
            'longitude' => 18.0686,
            'accuracy' => 12.5,
            'source' => 'site_visit',
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseHas('location_logs', [
            'user_id' => $user->id,
            'latitude' => 59.3293,
            'longitude' => 18.0686,
            'source' => 'site_visit',
        ]);
    }

    public function test_clock_in_creates_location_log_when_coordinates_sent(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/time-entries/clock-in', [
            'latitude' => 55.604981,
            'longitude' => 13.003822,
            'accuracy' => 20,
        ])->assertCreated();

        $this->assertDatabaseHas('location_logs', [
            'user_id' => $user->id,
            'source' => LocationLog::SOURCE_CLOCK_IN,
        ]);
    }

    public function test_user_only_sees_own_location_logs(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        LocationLog::recordForUser($a, 1.0, 2.0, null, LocationLog::SOURCE_MANUAL);
        LocationLog::recordForUser($b, 3.0, 4.0, null, LocationLog::SOURCE_MANUAL);

        Sanctum::actingAs($a);

        $response = $this->getJson('/api/v1/location-logs');
        $response->assertOk();
        $ids = collect($response->json('data.location_logs'))->pluck('id');
        $this->assertCount(1, $ids);
    }
}
