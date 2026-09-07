<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_user_id',
        'device_ip',
        'punch_time',
        'punch_type',
        'verification_type',
        'work_code',
        'is_processed',
    ];

    protected $casts = [
        'punch_time' => 'datetime',
        'is_processed' => 'boolean',
    ];

    /**
     * Get the user that owns the attendance log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get unprocessed logs.
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('is_processed', false);
    }

    /**
     * Scope to get logs by device.
     */
    public function scopeByDevice($query, $deviceIp)
    {
        return $query->where('device_ip', $deviceIp);
    }
}
