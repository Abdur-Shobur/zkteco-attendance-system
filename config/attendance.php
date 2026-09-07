<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Office hours (used for Late / Absent reports)
    |--------------------------------------------------------------------------
    */
    'work_start' => env('ATTENDANCE_WORK_START', '09:00'),
    'work_end' => env('ATTENDANCE_WORK_END', '18:00'),

    // Minutes after work_start before a clock-in is marked Late
    'late_grace_minutes' => (int) env('ATTENDANCE_LATE_GRACE', 15),

    // Weekdays counted for Absent (0=Sunday ... 6=Saturday)
    'working_days' => [1, 2, 3, 4, 5], // Mon–Fri
];
