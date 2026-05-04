<?php

namespace Database\Factories;

use App\Enums\TimeEntryApprovalStatus;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    protected $model = TimeEntry::class;

    public function definition(): array
    {
        $in = fake()->dateTimeBetween('-1 week', '-1 day');

        return [
            'user_id' => User::factory(),
            'clocked_in_at' => $in,
            'clocked_out_at' => (clone $in)->modify('+4 hours'),
            'approval_status' => TimeEntryApprovalStatus::Draft,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => TimeEntryApprovalStatus::Submitted,
            'submitted_at' => now(),
            'rejection_reason' => null,
        ]);
    }
}
