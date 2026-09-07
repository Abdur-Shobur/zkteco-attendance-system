<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AttendanceReportService
{
    public function generate(Carbon $startDate, Carbon $endDate, ?string $status = null, ?string $userId = null): array
    {
        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->endOfDay();

        $workStart = config('attendance.work_start', '09:00');
        $grace = (int) config('attendance.late_grace_minutes', 15);
        $workingDays = config('attendance.working_days', [1, 2, 3, 4, 5]);

        $usersQuery = User::query()
            ->whereNotNull('device_user_id')
            ->where('device_user_id', '!=', '');

        if ($userId) {
            $usersQuery->where(function ($q) use ($userId) {
                $q->where('id', $userId)->orWhere('device_user_id', $userId);
            });
        }

        $users = $usersQuery->orderBy('name')->get();

        $logs = AttendanceLog::query()
            ->with('user')
            ->whereBetween('punch_time', [$start, $end])
            ->when($userId, function ($q) use ($userId) {
                $q->where(function ($inner) use ($userId) {
                    $inner->where('user_id', $userId)
                        ->orWhere('device_user_id', $userId);
                });
            })
            ->orderBy('punch_time')
            ->get();

        $logsByUserDate = $logs->groupBy(function (AttendanceLog $log) {
            $key = $log->user_id ?: ('device:' . $log->device_user_id);
            return $key . '|' . $log->punch_time->format('Y-m-d');
        });

        $rows = [];
        $period = CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay());

        foreach ($period as $day) {
            /** @var Carbon $day */
            $date = $day->format('Y-m-d');
            $isWorkingDay = in_array((int) $day->dayOfWeek, $workingDays, true);

            foreach ($users as $user) {
                $groupKey = $user->id . '|' . $date;
                $dayLogs = $logsByUserDate->get($groupKey, collect());

                // Also match orphan logs by device_user_id
                if ($dayLogs->isEmpty() && $user->device_user_id) {
                    $dayLogs = $logsByUserDate->get('device:' . $user->device_user_id . '|' . $date, collect());
                }

                $row = $this->buildDayRow($user, $day, $dayLogs, $workStart, $grace, $isWorkingDay);

                if ($status && $status !== 'all' && $row['status'] !== $status) {
                    continue;
                }

                // Skip weekend absents unless they punched
                if (!$isWorkingDay && $row['status'] === 'weekend' && $status && $status !== 'weekend') {
                    continue;
                }

                $rows[] = $row;
            }

            // Device users not in users table (orphan punches)
            if (!$userId) {
                $orphanKeys = $logs
                    ->filter(fn (AttendanceLog $log) => !$log->user_id && $log->punch_time->format('Y-m-d') === $date)
                    ->pluck('device_user_id')
                    ->unique()
                    ->filter(function ($deviceUserId) use ($users) {
                        return !$users->contains(fn ($u) => (string) $u->device_user_id === (string) $deviceUserId);
                    });

                foreach ($orphanKeys as $deviceUserId) {
                    $dayLogs = $logsByUserDate->get('device:' . $deviceUserId . '|' . $date, collect());
                    $fakeUser = (object) [
                        'id' => null,
                        'name' => 'Device User ' . $deviceUserId,
                        'device_user_id' => $deviceUserId,
                        'employee_id' => $deviceUserId,
                    ];
                    $row = $this->buildDayRow($fakeUser, $day, $dayLogs, $workStart, $grace, $isWorkingDay);
                    if ($status && $status !== 'all' && $row['status'] !== $status) {
                        continue;
                    }
                    $rows[] = $row;
                }
            }
        }

        $summary = [
            'present' => collect($rows)->where('status', 'present')->count(),
            'late' => collect($rows)->where('status', 'late')->count(),
            'absent' => collect($rows)->where('status', 'absent')->count(),
            'incomplete' => collect($rows)->where('status', 'incomplete')->count(),
            'total_rows' => count($rows),
        ];

        return [
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => $status ?: 'all',
                'work_start' => $workStart,
                'late_grace_minutes' => $grace,
            ],
            'summary' => $summary,
            'rows' => $rows,
        ];
    }

    private function buildDayRow(object $user, Carbon $day, Collection $dayLogs, string $workStart, int $grace, bool $isWorkingDay): array
    {
        $date = $day->format('Y-m-d');
        $clockIn = null;
        $clockOut = null;

        if ($dayLogs->isNotEmpty()) {
            $sorted = $dayLogs->sortBy('punch_time')->values();
            $clockIn = $sorted->first()->punch_time;
            $last = $sorted->last()->punch_time;
            // Only treat as clock-out if later than first punch
            if ($sorted->count() > 1 && $last->ne($clockIn)) {
                $clockOut = $last;
            }
        }

        $status = 'absent';
        $lateByMinutes = null;

        if (!$isWorkingDay && !$clockIn) {
            $status = 'weekend';
        } elseif ($clockIn) {
            $threshold = Carbon::parse($date . ' ' . $workStart)->addMinutes($grace);
            if ($clockIn->gt($threshold)) {
                $status = 'late';
                $lateByMinutes = $clockIn->diffInMinutes(Carbon::parse($date . ' ' . $workStart));
            } elseif (!$clockOut) {
                $status = 'incomplete'; // punched in but no out yet
            } else {
                $status = 'present';
            }
        } elseif ($isWorkingDay) {
            $status = 'absent';
        }

        return [
            'date' => $date,
            'day_name' => $day->format('D'),
            'user_id' => $user->id ?? null,
            'user_name' => $user->name ?? 'Unknown',
            'device_user_id' => $user->device_user_id ?? null,
            'clock_in' => $clockIn?->format('h:i:s A'),
            'clock_out' => $clockOut?->format('h:i:s A'),
            'clock_in_raw' => $clockIn?->toDateTimeString(),
            'clock_out_raw' => $clockOut?->toDateTimeString(),
            'worked_hours' => ($clockIn && $clockOut)
                ? round($clockIn->floatDiffInHours($clockOut), 2)
                : null,
            'status' => $status,
            'late_by_minutes' => $lateByMinutes,
            'punches' => $dayLogs->count(),
        ];
    }
}
