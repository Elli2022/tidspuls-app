<?php

namespace App\Http\Controllers\Api;

use App\Enums\TimeEntryApprovalStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClockActionRequest;
use App\Http\Requests\Api\RejectTimeEntryRequest;
use App\Http\Requests\Api\StoreTimeEntryRequest;
use App\Http\Requests\Api\UpdateTimeEntryRequest;
use App\Models\LocationLog;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class TimeEntryController extends Controller
{
    public function pendingReview(Request $request)
    {
        $actor = $request->user();

        if (! in_array($actor->role, [UserRole::Admin, UserRole::Manager], true)) {
            return $this->errorResponse('forbidden', 'Du saknar behörighet.', 403);
        }

        if ($actor->organization_id === null) {
            return $this->successResponse(['time_entries' => []]);
        }

        $entries = TimeEntry::query()
            ->with(['user:id,name,email'])
            ->where('approval_status', TimeEntryApprovalStatus::Submitted)
            ->whereHas('user', fn ($q) => $q->where('organization_id', $actor->organization_id))
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get();

        return $this->successResponse([
            'time_entries' => $entries,
        ]);
    }

    public function submit(Request $request, TimeEntry $timeEntry)
    {
        abort_unless($timeEntry->user_id === $request->user()->id, 404);

        if (! $timeEntry->isEditableByOwner()) {
            return $this->errorResponse(
                'time_entry_not_submittable',
                'Stämplingen kan inte skickas för attest i detta läge.',
                422
            );
        }

        if ($timeEntry->clocked_out_at === null) {
            return $this->errorResponse(
                'time_entry_not_submittable',
                'Stämplingen måste vara avslutad (utstämplad) innan attest.',
                422
            );
        }

        $timeEntry->update([
            'approval_status' => TimeEntryApprovalStatus::Submitted,
            'submitted_at' => now(),
            'rejection_reason' => null,
        ]);

        return $this->successResponse([
            'time_entry' => $timeEntry->fresh(),
        ]);
    }

    public function approve(Request $request, TimeEntry $timeEntry)
    {
        $actor = $request->user();
        $timeEntry->loadMissing('user');

        if (! $this->actorManagesEntryOrganization($actor, $timeEntry)) {
            return $this->errorResponse('forbidden', 'Du saknar behörighet.', 403);
        }

        if ($timeEntry->user_id === $actor->id) {
            return $this->errorResponse(
                'cannot_approve_own_entry',
                'Du kan inte attestera din egen stämpling.',
                422
            );
        }

        if ($timeEntry->approval_status !== TimeEntryApprovalStatus::Submitted) {
            return $this->errorResponse(
                'time_entry_not_pending_review',
                'Stämplingen väntar inte på attest.',
                422
            );
        }

        $timeEntry->update([
            'approval_status' => TimeEntryApprovalStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $actor->id,
            'rejection_reason' => null,
        ]);

        return $this->successResponse([
            'time_entry' => $timeEntry->fresh()->load(['approver:id,name', 'user:id,name,email']),
        ]);
    }

    public function reject(RejectTimeEntryRequest $request, TimeEntry $timeEntry)
    {
        $actor = $request->user();
        $timeEntry->loadMissing('user');

        if (! $this->actorManagesEntryOrganization($actor, $timeEntry)) {
            return $this->errorResponse('forbidden', 'Du saknar behörighet.', 403);
        }

        if ($timeEntry->user_id === $actor->id) {
            return $this->errorResponse(
                'cannot_approve_own_entry',
                'Du kan inte attestera din egen stämpling.',
                422
            );
        }

        if ($timeEntry->approval_status !== TimeEntryApprovalStatus::Submitted) {
            return $this->errorResponse(
                'time_entry_not_pending_review',
                'Stämplingen väntar inte på attest.',
                422
            );
        }

        $reason = $request->validated()['reason'] ?? null;

        $timeEntry->update([
            'approval_status' => TimeEntryApprovalStatus::Rejected,
            'approved_at' => null,
            'approved_by' => null,
            'rejection_reason' => $reason,
        ]);

        return $this->successResponse([
            'time_entry' => $timeEntry->fresh()->load(['user:id,name,email']),
        ]);
    }

    public function index(Request $request)
    {
        $entries = $request->user()
            ->timeEntries()
            ->with(['approver:id,name'])
            ->orderByDesc('clocked_in_at')
            ->get();

        return $this->successResponse([
            'time_entries' => $entries,
        ]);
    }

    public function store(StoreTimeEntryRequest $request)
    {
        $data = $request->validated();

        if ($this->hasOverlap($request->user()->id, $data['clocked_in_at'], $data['clocked_out_at'] ?? null)) {
            return $this->errorResponse(
                'time_entry_overlap',
                'Time entry overlaps an existing entry.',
                422
            );
        }

        $entry = $request->user()->timeEntries()->create([
            ...$data,
            'approval_status' => TimeEntryApprovalStatus::Draft,
        ]);

        return $this->successResponse([
            'time_entry' => $entry,
        ], 201);
    }

    public function update(UpdateTimeEntryRequest $request, TimeEntry $timeEntry)
    {
        abort_unless($timeEntry->user_id === $request->user()->id, 404);

        if (! $timeEntry->isEditableByOwner()) {
            return $this->errorResponse(
                'time_entry_not_editable',
                'Du kan inte redigera en stämpling som väntar på attest eller redan är godkänd.',
                422
            );
        }

        $data = $request->validated();

        if ($this->hasOverlap(
            $request->user()->id,
            $data['clocked_in_at'],
            $data['clocked_out_at'] ?? null,
            $timeEntry->id
        )) {
            return $this->errorResponse(
                'time_entry_overlap',
                'Time entry overlaps an existing entry.',
                422
            );
        }

        $timeEntry->update($data);

        return $this->successResponse([
            'time_entry' => $timeEntry->fresh()->load(['approver:id,name']),
        ]);
    }

    public function destroy(Request $request, TimeEntry $timeEntry)
    {
        abort_unless($timeEntry->user_id === $request->user()->id, 404);

        if (! $timeEntry->isEditableByOwner()) {
            return $this->errorResponse(
                'time_entry_not_editable',
                'Du kan inte ta bort en stämpling som väntar på attest eller redan är godkänd.',
                422
            );
        }

        $timeEntry->delete();

        return $this->successResponse([
            'message' => 'Time entry deleted.',
        ]);
    }

    public function clockIn(ClockActionRequest $request)
    {
        $openEntry = $request->user()->timeEntries()->whereNull('clocked_out_at')->first();

        if ($openEntry !== null) {
            return $this->errorResponse(
                'open_time_entry_exists',
                'You already have an active clock-in.',
                422
            );
        }

        $payload = $request->validated();
        $entry = $request->user()->timeEntries()->create([
            'clocked_in_at' => CarbonImmutable::now(),
            'clock_in_latitude' => $payload['latitude'] ?? null,
            'clock_in_longitude' => $payload['longitude'] ?? null,
            'approval_status' => TimeEntryApprovalStatus::Draft,
        ]);

        if (($payload['latitude'] ?? null) !== null && ($payload['longitude'] ?? null) !== null) {
            LocationLog::recordForUser(
                $request->user(),
                (float) $payload['latitude'],
                (float) $payload['longitude'],
                isset($payload['accuracy']) ? (float) $payload['accuracy'] : null,
                LocationLog::SOURCE_CLOCK_IN,
            );
        }

        return $this->successResponse([
            'time_entry' => $entry,
        ], 201);
    }

    public function clockOut(ClockActionRequest $request)
    {
        $entry = $request->user()
            ->timeEntries()
            ->whereNull('clocked_out_at')
            ->latest('clocked_in_at')
            ->first();

        if ($entry === null) {
            return $this->errorResponse(
                'no_open_time_entry',
                'No active clock-in found.',
                422
            );
        }

        $payload = $request->validated();
        $entry->update([
            'clocked_out_at' => CarbonImmutable::now(),
            'clock_out_latitude' => $payload['latitude'] ?? null,
            'clock_out_longitude' => $payload['longitude'] ?? null,
        ]);

        if (($payload['latitude'] ?? null) !== null && ($payload['longitude'] ?? null) !== null) {
            LocationLog::recordForUser(
                $request->user(),
                (float) $payload['latitude'],
                (float) $payload['longitude'],
                isset($payload['accuracy']) ? (float) $payload['accuracy'] : null,
                LocationLog::SOURCE_CLOCK_OUT,
            );
        }

        return $this->successResponse([
            'time_entry' => $entry->fresh(),
        ]);
    }

    private function actorManagesEntryOrganization(User $actor, TimeEntry $entry): bool
    {
        if (! in_array($actor->role, [UserRole::Admin, UserRole::Manager], true)) {
            return false;
        }

        $orgId = $actor->organization_id;

        return $orgId !== null
            && $entry->user !== null
            && $entry->user->organization_id === $orgId;
    }

    private function hasOverlap(
        int $userId,
        string $clockedInAt,
        ?string $clockedOutAt,
        ?int $excludeId = null
    ): bool {
        $start = CarbonImmutable::parse($clockedInAt);
        $end = $clockedOutAt ? CarbonImmutable::parse($clockedOutAt) : null;

        $query = TimeEntry::query()
            ->where('user_id', $userId)
            ->when($excludeId !== null, fn ($q) => $q->where('id', '!=', $excludeId));

        $infinity = CarbonImmutable::parse('9999-12-31 23:59:59');

        return $query->get()->contains(function (TimeEntry $entry) use ($start, $end, $infinity) {
            $entryStart = CarbonImmutable::parse($entry->clocked_in_at);
            $entryEnd = $entry->clocked_out_at
                ? CarbonImmutable::parse($entry->clocked_out_at)
                : null;

            // Open interval for active entries: [start, +infinity)
            if ($end === null || $entryEnd === null) {
                return $start < ($entryEnd ?? $infinity)
                    && $entryStart < ($end ?? $infinity);
            }

            return $start < $entryEnd && $entryStart < $end;
        });
    }
}
