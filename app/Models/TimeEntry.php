<?php

namespace App\Models;

use App\Enums\TimeEntryApprovalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'clocked_in_at',
        'clocked_out_at',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_out_latitude',
        'clock_out_longitude',
        'note',
        'approval_status',
        'submitted_at',
        'approved_at',
        'approved_by',
        'rejection_reason',
    ];

    protected $casts = [
        'clocked_in_at' => 'datetime',
        'clocked_out_at' => 'datetime',
        'clock_in_latitude' => 'float',
        'clock_in_longitude' => 'float',
        'clock_out_latitude' => 'float',
        'clock_out_longitude' => 'float',
        'approval_status' => TimeEntryApprovalStatus::class,
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isEditableByOwner(): bool
    {
        return in_array($this->approval_status, [
            TimeEntryApprovalStatus::Draft,
            TimeEntryApprovalStatus::Rejected,
        ], true);
    }
}
