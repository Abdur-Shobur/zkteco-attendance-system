<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'device_user_id',
        'employee_id',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, mixed>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
    ];

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * Get the attendance logs for the user.
     */
    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    /**
     * Get the latest attendance log for the user.
     */
    public function latestAttendanceLog()
    {
        return $this->hasOne(AttendanceLog::class)->latestOfMany();
    }

    /**
     * Get attendance logs for a specific date range.
     */
    public function attendanceLogsBetween($startDate, $endDate)
    {
        return $this->attendanceLogs()
            ->whereBetween('punch_time', [$startDate, $endDate])
            ->orderBy('punch_time');
    }
}
