<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationLog extends Model
{
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_CLOCK_IN = 'clock_in';

    public const SOURCE_CLOCK_OUT = 'clock_out';

    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'accuracy',
        'source',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function recordForUser(
        User $user,
        float $latitude,
        float $longitude,
        ?float $accuracy,
        string $source
    ): self {
        return $user->locationLogs()->create([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'source' => $source,
            'recorded_at' => CarbonImmutable::now(),
        ]);
    }
}
