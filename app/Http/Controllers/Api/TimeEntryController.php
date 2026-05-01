<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClockActionRequest;
use App\Http\Requests\Api\StoreTimeEntryRequest;
use App\Http\Requests\Api\UpdateTimeEntryRequest;
use App\Models\TimeEntry;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class TimeEntryController extends Controller
{
    public function index(Request $request)
    {
        $entries = $request->user()
            ->timeEntries()
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

        $entry = $request->user()->timeEntries()->create($data);

        return $this->successResponse([
            'time_entry' => $entry,
        ], 201);
    }

    public function update(UpdateTimeEntryRequest $request, TimeEntry $timeEntry)
    {
        abort_unless($timeEntry->user_id === $request->user()->id, 404);

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
            'time_entry' => $timeEntry->fresh(),
        ]);
    }

    public function destroy(Request $request, TimeEntry $timeEntry)
    {
        abort_unless($timeEntry->user_id === $request->user()->id, 404);

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
        ]);

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

        return $this->successResponse([
            'time_entry' => $entry->fresh(),
        ]);
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
