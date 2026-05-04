<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizationMembersApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_organization_members(): void
    {
        $organization = Organization::factory()->create();

        $admin = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Admin,
        ]);

        User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Employee,
            'name' => 'Medarbetare Etta',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/organization/members');

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $names = collect($response->json('data.members'))->pluck('name')->sort()->values()->all();

        $this->assertContains($admin->name, $names);
        $this->assertContains('Medarbetare Etta', $names);
    }

    public function test_employee_cannot_list_organization_members(): void
    {
        $organization = Organization::factory()->create();

        $employee = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Employee,
        ]);

        Sanctum::actingAs($employee);

        $this->getJson('/api/v1/organization/members')->assertStatus(403);
    }
}
