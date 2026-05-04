<?php

namespace Tests\Feature\Api;

use App\Enums\TimeEntryApprovalStatus;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TimeEntryAttestApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_submit_closed_draft_entry(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $entry = TimeEntry::factory()->for($user)->create([
            'clocked_out_at' => now()->subHour(),
            'approval_status' => TimeEntryApprovalStatus::Draft,
        ]);

        $response = $this->postJson("/api/v1/time-entries/{$entry->id}/submit");

        $response->assertOk()->assertJsonPath('data.time_entry.approval_status', 'submitted');
        $this->assertNotNull($response->json('data.time_entry.submitted_at'));
    }

    public function test_employee_cannot_submit_open_entry(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $entry = TimeEntry::factory()->for($user)->create([
            'clocked_out_at' => null,
            'approval_status' => TimeEntryApprovalStatus::Draft,
        ]);

        $this->postJson("/api/v1/time-entries/{$entry->id}/submit")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'time_entry_not_submittable');
    }

    public function test_employee_cannot_update_submitted_entry(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $entry = TimeEntry::factory()->for($user)->submitted()->create([
            'clocked_in_at' => '2026-04-26T08:00:00Z',
            'clocked_out_at' => '2026-04-26T12:00:00Z',
        ]);

        $this->putJson("/api/v1/time-entries/{$entry->id}", [
            'clocked_in_at' => '2026-04-26T09:00:00Z',
            'clocked_out_at' => '2026-04-26T13:00:00Z',
            'note' => 'Ändring',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'time_entry_not_editable');
    }

    public function test_manager_can_approve_submitted_entry_from_same_org(): void
    {
        $organization = Organization::factory()->create();

        $manager = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Manager,
        ]);

        $employee = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Employee,
        ]);

        $entry = TimeEntry::factory()->for($employee)->submitted()->create([
            'clocked_in_at' => '2026-04-26T08:00:00Z',
            'clocked_out_at' => '2026-04-26T12:00:00Z',
        ]);

        Sanctum::actingAs($manager);

        $response = $this->postJson("/api/v1/time-entries/{$entry->id}/approve");

        $response->assertOk()->assertJsonPath('data.time_entry.approval_status', 'approved');
        $this->assertSame($manager->id, $response->json('data.time_entry.approved_by'));
    }

    public function test_manager_cannot_approve_own_entry(): void
    {
        $organization = Organization::factory()->create();

        $manager = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Manager,
        ]);

        $entry = TimeEntry::factory()->for($manager)->submitted()->create([
            'clocked_in_at' => '2026-04-26T08:00:00Z',
            'clocked_out_at' => '2026-04-26T12:00:00Z',
        ]);

        Sanctum::actingAs($manager);

        $this->postJson("/api/v1/time-entries/{$entry->id}/approve")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'cannot_approve_own_entry');
    }

    public function test_manager_can_reject_with_optional_reason(): void
    {
        $organization = Organization::factory()->create();

        $manager = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Manager,
        ]);

        $employee = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Employee,
        ]);

        $entry = TimeEntry::factory()->for($employee)->submitted()->create([
            'clocked_in_at' => '2026-04-26T08:00:00Z',
            'clocked_out_at' => '2026-04-26T12:00:00Z',
        ]);

        Sanctum::actingAs($manager);

        $response = $this->postJson("/api/v1/time-entries/{$entry->id}/reject", [
            'reason' => 'Tiderna stämmer inte.',
        ]);

        $response->assertOk()->assertJsonPath('data.time_entry.approval_status', 'rejected');
        $this->assertSame('Tiderna stämmer inte.', $response->json('data.time_entry.rejection_reason'));
    }

    public function test_rejected_entry_can_be_edited_and_resubmitted(): void
    {
        $organization = Organization::factory()->create();

        $manager = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Manager,
        ]);

        $employee = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Employee,
        ]);

        $entry = TimeEntry::factory()->for($employee)->submitted()->create([
            'clocked_in_at' => '2026-04-26T08:00:00Z',
            'clocked_out_at' => '2026-04-26T12:00:00Z',
        ]);

        Sanctum::actingAs($manager);
        $this->postJson("/api/v1/time-entries/{$entry->id}/reject", ['reason' => 'Fel'])->assertOk();

        Sanctum::actingAs($employee);

        $this->putJson("/api/v1/time-entries/{$entry->id}", [
            'clocked_in_at' => '2026-04-26T09:00:00Z',
            'clocked_out_at' => '2026-04-26T13:00:00Z',
            'note' => null,
        ])->assertOk();

        $this->postJson("/api/v1/time-entries/{$entry->id}/submit")->assertOk()
            ->assertJsonPath('data.time_entry.approval_status', 'submitted');
    }

    public function test_employee_cannot_access_pending_review_list(): void
    {
        $user = User::factory()->create(['role' => UserRole::Employee]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/time-entries/pending-review')->assertStatus(403);
    }

    public function test_manager_cannot_approve_entry_from_other_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $manager = User::factory()->create([
            'organization_id' => $orgA->id,
            'role' => UserRole::Manager,
        ]);

        $outsider = User::factory()->create([
            'organization_id' => $orgB->id,
            'role' => UserRole::Employee,
        ]);

        $entry = TimeEntry::factory()->for($outsider)->submitted()->create([
            'clocked_in_at' => '2026-04-26T08:00:00Z',
            'clocked_out_at' => '2026-04-26T12:00:00Z',
        ]);

        Sanctum::actingAs($manager);

        $this->postJson("/api/v1/time-entries/{$entry->id}/approve")->assertStatus(403);
    }
}
