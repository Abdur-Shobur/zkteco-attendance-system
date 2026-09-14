<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeSetting extends Model
{
    protected $fillable = [
        'work_start',
        'work_end',
        'late_grace_minutes',
        'working_days',
        'holidays',
    ];

    protected $casts = [
        'working_days' => 'array',
        'holidays' => 'array',
        'late_grace_minutes' => 'integer',
    ];

    public static function current(): self
    {
        $settings = static::query()->first();

        if ($settings) {
            return $settings;
        }

        return static::create([
            'work_start' => config('attendance.work_start', '09:00'),
            'work_end' => config('attendance.work_end', '18:00'),
            'late_grace_minutes' => (int) config('attendance.late_grace_minutes', 15),
            'working_days' => config('attendance.working_days', [1, 2, 3, 4, 5]),
            'holidays' => [],
        ]);
    }

    public function formatTime12(?string $time): string
    {
        if (!$time) {
            return '—';
        }

        try {
            return \Carbon\Carbon::createFromFormat('H:i', substr($time, 0, 5))->format('g:i A');
        } catch (\Throwable $e) {
            return $time;
        }
    }

    public function workHoursLabel(): string
    {
        return $this->formatTime12($this->work_start) . ' – ' . $this->formatTime12($this->work_end);
    }

    public function offDayLabels(): array
    {
        $names = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        $working = collect($this->working_days ?? [1, 2, 3, 4, 5])->map(fn ($d) => (int) $d);
        $off = collect(range(0, 6))->reject(fn ($d) => $working->contains($d));

        return $off->map(fn ($d) => $names[$d])->values()->all();
    }

    public function isOffDay(\Carbon\Carbon $day): bool
    {
        $working = collect($this->working_days ?? [1, 2, 3, 4, 5])->map(fn ($d) => (int) $d)->all();
        if (!in_array((int) $day->dayOfWeek, $working, true)) {
            return true;
        }

        $holidays = collect($this->holidays ?? [])->map(fn ($d) => (string) $d)->all();
        return in_array($day->toDateString(), $holidays, true);
    }
}
