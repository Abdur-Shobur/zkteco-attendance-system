<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\OfficeSetting;
use App\Models\User;
use App\Services\AttendanceReportService;
use App\Services\ZKTecoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    private $zkTecoService;

    public function __construct()
    {
        $this->zkTecoService = new ZKTecoService();
    }

    /**
     * Display attendance logs dashboard
     */
    public function index(): View
    {
        $recentLogs = AttendanceLog::with('user')
            ->orderBy('punch_time', 'desc')
            ->limit(8)
            ->get();

        $office = OfficeSetting::current();

        $stats = [
            'total_logs' => AttendanceLog::count(),
            'today_logs' => AttendanceLog::whereDate('punch_time', Carbon::today())->count(),
            'users' => User::whereNotNull('device_user_id')->where('device_user_id', '!=', '')->count(),
        ];

        return view('attendance.index', compact('recentLogs', 'stats', 'office'));
    }

    /**
     * Office settings page
     */
    public function settingsPage(): View
    {
        $office = OfficeSetting::current();
        $dayNames = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return view('attendance.settings', compact('office', 'dayNames'));
    }

    /**
     * Save office settings
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'work_start' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'work_end' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'late_grace_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['integer', 'between:0,6'],
            'holidays' => ['nullable', 'string'],
        ]);

        $workStart = substr($data['work_start'], 0, 5);
        $workEnd = substr($data['work_end'], 0, 5);

        if ($workEnd <= $workStart) {
            return back()
                ->withErrors(['work_end' => 'Work end must be after work start.'])
                ->withInput();
        }

        $holidays = collect(preg_split('/\r\n|\r|\n|,/', (string) ($data['holidays'] ?? '')))
            ->map(fn ($d) => trim($d))
            ->filter()
            ->filter(function ($d) {
                try {
                    return Carbon::parse($d)->toDateString();
                } catch (\Throwable $e) {
                    return false;
                }
            })
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->sort()
            ->values()
            ->all();

        $office = OfficeSetting::current();
        $office->update([
            'work_start' => $workStart,
            'work_end' => $workEnd,
            'late_grace_minutes' => $data['late_grace_minutes'],
            'working_days' => array_map('intval', $data['working_days']),
            'holidays' => $holidays,
        ]);

        return redirect()
            ->route('attendance.settings.page')
            ->with('success', 'Office settings saved. Reports will use the new rules.');
    }

    /**
     * Attendance logs page (HTML)
     */
    public function logsPage(Request $request): View
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $deviceUserId = $request->get('device_user_id');

        $query = AttendanceLog::with('user')->orderBy('punch_time', 'desc');

        if ($startDate) {
            $query->where('punch_time', '>=', Carbon::parse($startDate)->startOfDay());
        }
        if ($endDate) {
            $query->where('punch_time', '<=', Carbon::parse($endDate)->endOfDay());
        }
        if ($deviceUserId) {
            $query->where('device_user_id', $deviceUserId);
        }

        $logs = $query->paginate(25);

        return view('attendance.logs', compact('logs', 'startDate', 'endDate', 'deviceUserId'));
    }

    /**
     * Users page (HTML)
     */
    public function usersPage(): View
    {
        $users = User::with('latestAttendanceLog')
            ->orderBy('name')
            ->paginate(25);

        $mappedCount = User::whereNotNull('device_user_id')->where('device_user_id', '!=', '')->count();
        $punchLinked = AttendanceLog::whereNotNull('user_id')->distinct()->count('user_id');

        return view('attendance.users', compact('users', 'mappedCount', 'punchLinked'));
    }

    /**
     * Admin own profile page
     */
    public function profilePage(): View
    {
        $user = auth()->user();
        return view('attendance.profile', [
            'user' => $user,
            'isOwnProfile' => true,
        ]);
    }

    /**
     * Update admin own profile
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        return $this->saveUserProfile($request, auth()->user(), true);
    }

    /**
     * Edit any user profile
     */
    public function editUserPage(User $user): View
    {
        return view('attendance.profile', [
            'user' => $user,
            'isOwnProfile' => auth()->id() === $user->id,
        ]);
    }

    /**
     * Update any user profile
     */
    public function updateUser(Request $request, User $user): RedirectResponse
    {
        return $this->saveUserProfile($request, $user, auth()->id() === $user->id);
    }

    /**
     * Create user form
     */
    public function createUserPage(): View
    {
        return view('attendance.user-form', [
            'user' => new User(),
            'isCreate' => true,
        ]);
    }

    /**
     * Store new user
     */
    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'device_user_id' => ['nullable', 'string', 'max:50'],
            'employee_id' => ['nullable', 'string', 'max:50'],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'device_user_id' => $data['device_user_id'] ?: null,
            'employee_id' => $data['employee_id'] ?: null,
            'is_admin' => $request->boolean('is_admin'),
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('attendance.users.page')
            ->with('success', 'User created successfully.');
    }

    private function saveUserProfile(Request $request, User $user, bool $isOwnProfile): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'device_user_id' => ['nullable', 'string', 'max:50'],
            'employee_id' => ['nullable', 'string', 'max:50'],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        $wantsAdmin = $request->boolean('is_admin');

        // Do not allow removing admin from the last admin account
        if ($user->is_admin && !$wantsAdmin) {
            $otherAdmins = User::where('is_admin', true)->where('id', '!=', $user->id)->count();
            if ($otherAdmins === 0) {
                return back()
                    ->withErrors(['is_admin' => 'You cannot remove admin access from the last admin account.'])
                    ->withInput();
            }
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->device_user_id = $data['device_user_id'] ?: null;
        $user->employee_id = $data['employee_id'] ?: null;
        $user->is_admin = $wantsAdmin;

        if (!empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        $redirect = $request->routeIs('attendance.profile.update')
            ? route('attendance.profile.page')
            : route('attendance.users.edit', $user);

        return redirect($redirect)->with('success', 'Profile updated successfully.');
    }

    /**
     * Device controls page
     */
    public function devicePage(): View
    {
        return view('attendance.device');
    }

    /**
     * Test device connection
     */
    public function testConnection(): JsonResponse
    {
        if (config('zkteco.mode', 'adms') === 'adms') {
            return response()->json(app(AdmsController::class)->status());
        }

        $result = $this->zkTecoService->testConnection();
        return response()->json($result);
    }

    /**
     * Get device information
     */
    public function getDeviceInfo(): JsonResponse
    {
        $info = $this->zkTecoService->getDeviceInfo();
        return response()->json($info);
    }

    /**
     * Sync attendance logs from device
     */
    public function syncLogs(): JsonResponse
    {
        $result = $this->zkTecoService->syncAttendanceLogs();
        return response()->json([
            'success' => true,
            'message' => "Synced {$result['synced_count']} out of {$result['total_logs']} logs",
            'data' => $result
        ]);
    }

    /**
     * Get device users
     */
    public function getDeviceUsers(): JsonResponse
    {
        if (config('zkteco.mode', 'adms') === 'adms') {
            $adms = app(\App\Services\AdmsService::class);
            $request = $adms->requestUserSync();
            $users = $request['users'];

            return response()->json([
                'mode' => 'adms',
                'users' => $users,
                'count' => count($users),
                'message' => $request['message'],
                'queued' => $request['queued'],
            ]);
        }

        $users = $this->zkTecoService->getDeviceUsers();
        return response()->json($users);
    }

    /**
     * Sync device users to database
     */
    public function syncDeviceUsers(): JsonResponse
    {
        if (config('zkteco.mode', 'adms') === 'adms') {
            $result = app(\App\Services\AdmsService::class)->syncUsersToDatabase();
            return response()->json([
                'success' => true,
                'message' => "Synced {$result['synced_count']} new, updated {$result['updated_count']}. {$result['adms_request']}",
                'data' => $result,
            ]);
        }

        $result = $this->zkTecoService->syncDeviceUsers();
        return response()->json([
            'success' => true,
            'message' => 'Device users synchronized successfully',
            'data' => $result
        ]);
    }

    /**
     * Get attendance logs with filters
     */
    public function getLogs(Request $request): JsonResponse
    {
        $query = AttendanceLog::with('user');

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('punch_time', '>=', Carbon::parse($request->start_date));
        }

        if ($request->has('end_date')) {
            $query->where('punch_time', '<=', Carbon::parse($request->end_date)->endOfDay());
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by device
        if ($request->has('device_ip')) {
            $query->where('device_ip', $request->device_ip);
        }

        // Filter by punch type
        if ($request->has('punch_type')) {
            $query->where('punch_type', $request->punch_type);
        }

        $logs = $query->orderBy('punch_time', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($logs);
    }

    /**
     * Get attendance summary for a user
     */
    public function getUserSummary(Request $request, $userId): JsonResponse
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth());

        $user = User::findOrFail($userId);
        
        $logs = AttendanceLog::where('user_id', $userId)
            ->whereBetween('punch_time', [$startDate, $endDate])
            ->orderBy('punch_time')
            ->get();

        $summary = [
            'user' => $user,
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'total_days' => $logs->groupBy(function($log) {
                return $log->punch_time->format('Y-m-d');
            })->count(),
            'total_check_ins' => $logs->where('punch_type', 'check_in')->count(),
            'total_check_outs' => $logs->where('punch_type', 'check_out')->count(),
            'logs' => $logs->groupBy(function($log) {
                return $log->punch_time->format('Y-m-d');
            })
        ];

        return response()->json($summary);
    }

    /**
     * Mark attendance logs as processed
     */
    public function markAsProcessed(Request $request): JsonResponse
    {
        $logIds = $request->input('log_ids', []);
        
        AttendanceLog::whereIn('id', $logIds)
            ->update(['is_processed' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Logs marked as processed',
            'processed_count' => count($logIds)
        ]);
    }

    /**
     * Clear attendance logs from device
     */
    public function clearDeviceLogs(): JsonResponse
    {
        $result = $this->zkTecoService->clearAttendanceLogs();
        
        return response()->json([
            'success' => $result,
            'message' => $result ? 'Device logs cleared successfully' : 'Failed to clear device logs'
        ]);
    }

    /**
     * Real-time sync endpoint (can be called via AJAX polling)
     */
    public function realtimeSync(): JsonResponse
    {
        $result = $this->zkTecoService->syncAttendanceLogs();
        
        return response()->json([
            'success' => true,
            'new_logs' => $result['synced_count'],
            'timestamp' => Carbon::now()->toISOString()
        ]);
    }

    /**
     * Attendance daily report (clock in/out, late, absent)
     */
    public function report(Request $request, AttendanceReportService $reportService): View
    {
        $startDate = Carbon::parse($request->get('start_date', Carbon::now()->toDateString()));
        $endDate = Carbon::parse($request->get('end_date', Carbon::now()->toDateString()));
        $status = $request->get('status', 'all');
        $userId = $request->get('user_id');

        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        // Cap range to 62 days to keep page usable
        if ($startDate->diffInDays($endDate) > 62) {
            $endDate = $startDate->copy()->addDays(62);
        }

        $report = $reportService->generate($startDate, $endDate, $status, $userId);
        $users = User::query()
            ->whereNotNull('device_user_id')
            ->where('device_user_id', '!=', '')
            ->orderBy('name')
            ->get(['id', 'name', 'device_user_id']);

        $office = OfficeSetting::current();

        return view('attendance.report', [
            'report' => $report,
            'users' => $users,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'status' => $status,
            'userId' => $userId,
            'workStart' => $office->work_start,
            'workEnd' => $office->work_end,
            'lateGrace' => $office->late_grace_minutes,
        ]);
    }

    /**
     * Export daily attendance report CSV
     */
    public function exportReport(Request $request, AttendanceReportService $reportService)
    {
        $startDate = Carbon::parse($request->get('start_date', Carbon::now()->toDateString()));
        $endDate = Carbon::parse($request->get('end_date', Carbon::now()->toDateString()));
        $status = $request->get('status', 'all');
        $userId = $request->get('user_id');

        $report = $reportService->generate($startDate, $endDate, $status, $userId);
        $filename = 'attendance_report_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($report) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Date', 'Day', 'Employee', 'Device ID', 'Clock In', 'Clock Out',
                'Worked Hours', 'Status', 'Late By (minutes)', 'Punches',
            ]);

            foreach ($report['rows'] as $row) {
                fputcsv($file, [
                    $row['date'],
                    $row['day_name'],
                    $row['user_name'],
                    $row['device_user_id'],
                    $row['clock_in'] ?? '',
                    $row['clock_out'] ?? '',
                    $row['worked_hours'] ?? '',
                    $row['status'],
                    $row['late_by_minutes'] ?? '',
                    $row['punches'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export attendance logs to CSV
     */
    public function exportLogs(Request $request)
    {
        $query = AttendanceLog::with('user');

        // Apply filters
        if ($request->has('start_date')) {
            $query->where('punch_time', '>=', Carbon::parse($request->start_date));
        }

        if ($request->has('end_date')) {
            $query->where('punch_time', '<=', Carbon::parse($request->end_date)->endOfDay());
        }

        $logs = $query->orderBy('punch_time')->get();

        $filename = 'attendance_logs_' . Carbon::now()->format('Y_m_d_H_i_s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID',
                'User Name',
                'Device User ID',
                'Device IP',
                'Punch Time',
                'Punch Type',
                'Verification Type',
                'Work Code',
                'Is Processed'
            ]);

            // CSV data
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->user ? $log->user->name : 'Unknown',
                    $log->device_user_id,
                    $log->device_ip,
                    $log->punch_time->format('Y-m-d H:i:s'),
                    $log->punch_type,
                    $log->verification_type,
                    $log->work_code,
                    $log->is_processed ? 'Yes' : 'No'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
