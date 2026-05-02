<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLocationLogRequest;
use App\Models\LocationLog;
use Illuminate\Http\Request;

class LocationLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = $request->user()
            ->locationLogs()
            ->orderByDesc('recorded_at')
            ->paginate(75);

        return $this->successResponse([
            'location_logs' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function store(StoreLocationLogRequest $request)
    {
        $payload = $request->validated();
        $source = $payload['source'] ?? LocationLog::SOURCE_MANUAL;

        $log = LocationLog::recordForUser(
            $request->user(),
            (float) $payload['latitude'],
            (float) $payload['longitude'],
            isset($payload['accuracy']) ? (float) $payload['accuracy'] : null,
            $source,
        );

        return $this->successResponse([
            'location_log' => $log,
        ], 201);
    }
}
