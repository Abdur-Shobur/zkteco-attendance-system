<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\User;
use Rats\Zkteco\Lib\ZKTeco;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

class ZKTecoService
{
    private $zk;
    private $deviceIp;
    private $devicePort;

    public function __construct($deviceIp = null, $devicePort = 4370)
    {
        $this->deviceIp = $deviceIp ?? config('zkteco.device_ip', '192.168.1.201');
        $this->devicePort = $devicePort ?? (int) config('zkteco.device_port', 4370);
        $this->zk = new ZKTeco($this->deviceIp, $this->devicePort);

        // rats/zkteco hardcodes ~60.5s; apply our configured timeout (default 60s)
        $timeout = (int) config('zkteco.connection_timeout', 60);
        if ($timeout < 1) {
            $timeout = 60;
        }
        socket_set_option($this->zk->_zkclient, SOL_SOCKET, SO_RCVTIMEO, [
            'sec' => $timeout,
            'usec' => 0,
        ]);
        socket_set_option($this->zk->_zkclient, SOL_SOCKET, SO_SNDTIMEO, [
            'sec' => $timeout,
            'usec' => 0,
        ]);
    }

    /**
     * Connect to the ZKTeco device
     */
    public function connect(): bool
    {
        try {
            $connection = $this->zk->connect();
            if ($connection) {
                Log::info("Successfully connected to ZKTeco device at {$this->deviceIp}:{$this->devicePort}");
                return true;
            }
            Log::error("Failed to connect to ZKTeco device at {$this->deviceIp}:{$this->devicePort}");
            return false;
        } catch (Exception $e) {
            Log::error("ZKTeco connection error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Disconnect from the ZKTeco device
     */
    public function disconnect(): bool
    {
        try {
            return $this->zk->disconnect();
        } catch (Exception $e) {
            Log::error("ZKTeco disconnect error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all attendance logs from the device
     */
    public function getAttendanceLogs(): array
    {
        if (!$this->connect()) {
            return [];
        }

        try {
            $logs = $this->zk->getAttendance();
            $this->disconnect();
            return $logs;
        } catch (Exception $e) {
            Log::error("Error retrieving attendance logs: " . $e->getMessage());
            $this->disconnect();
            return [];
        }
    }

    /**
     * Get users from the device
     */
    public function getDeviceUsers(): array
    {
        if (!$this->connect()) {
            return [];
        }

        try {
            $users = $this->zk->getUser();
            $this->disconnect();
            return $users;
        } catch (Exception $e) {
            Log::error("Error retrieving device users: " . $e->getMessage());
            $this->disconnect();
            return [];
        }
    }

    /**
     * Sync device users to database
     */
    public function syncDeviceUsers(): array
    {
        $deviceUsers = $this->getDeviceUsers();
        $syncedCount = 0;
        $updatedCount = 0;
        $errors = [];

        foreach ($deviceUsers as $deviceUser) {
            try {
                // Check if user already exists by device_user_id
                $existingUser = User::where('device_user_id', $deviceUser['userid'])->first();

                if ($existingUser) {
                    // Update existing user
                    $existingUser->update([
                        'name' => $deviceUser['name'],
                        'employee_id' => $deviceUser['userid'],
                    ]);
                    $updatedCount++;
                    Log::info("Updated existing user: {$deviceUser['name']} (ID: {$deviceUser['userid']})");
                } else {
                    // Create new user
                    $email = strtolower(str_replace(' ', '.', $deviceUser['name'])) . '@limerick.com';
                    $staffEmailChk = User::where("email", $email)->first();
                    if (!is_null($staffEmailChk)) {
                        $email = strtolower(str_replace(' ', '.', $deviceUser['name'])) . rand(1000, 9999) . '@limerick.com';
                    }
                    User::create([
                        'name' => $deviceUser['name'],
                        'email' => strtolower(str_replace(' ', '.', $deviceUser['name'])) . '@limerick.com', // Generate email
                        'password' => bcrypt('12345678'), // Default password
                        'device_user_id' => $deviceUser['userid'],
                        'employee_id' => $deviceUser['userid'],
                        'is_admin' => false,
                    ]);
                    $syncedCount++;
                }
            } catch (Exception $e) {
                $errors[] = "Error syncing user {$deviceUser['userid']}: " . $e->getMessage();
                Log::error("Error syncing device user: " . $e->getMessage(), $deviceUser);
            }
        }

        return [
            'total_device_users' => count($deviceUsers),
            'synced_count' => $syncedCount,
            'updated_count' => $updatedCount,
            'errors' => $errors
        ];
    }

    /**
     * Sync attendance logs from device to database
     */
    public function syncAttendanceLogs(): array
    {
        $logs = $this->getAttendanceLogs();
        $syncedCount = 0;
        $errors = [];
        foreach ($logs as $log) {
            try {
                $existingLog = AttendanceLog::where('device_user_id', $log['id'])
                    ->where('punch_time', Carbon::parse($log['timestamp']))
                    ->where('device_ip', $this->deviceIp)
                    ->first();

                if (!$existingLog) {
                    $attendanceLog = new AttendanceLog([
                        'device_user_id' => $log['id'],
                        'device_ip' => $this->deviceIp,
                        'punch_time' => Carbon::parse($log['timestamp']),
                        'punch_type' => $this->determinePunchType($log),
                        'verification_type' => $this->getVerificationType($log['type'] ?? null),
                        'work_code' => $log['status'] ?? null,
                        'is_processed' => false,
                    ]);

                    // Try to map device user to Laravel user
                    $user = $this->mapDeviceUserToLaravelUser($log['id']);
                    if ($user) {
                        $attendanceLog->user_id = $user->id;
                    }

                    $attendanceLog->save();
                    $syncedCount++;
                }
            } catch (Exception $e) {
                $errors[] = "Error syncing log for user {$log['id']}: " . $e->getMessage();
                Log::error("Error syncing attendance log: " . $e->getMessage(), $log);
            }
        }

        return [
            'total_logs' => count($logs),
            'synced_count' => $syncedCount,
            'errors' => $errors
        ];
    }

    /**
     * Map device user ID to Laravel user
     */
    private function mapDeviceUserToLaravelUser($deviceUserId): ?User
    {
        // Try to find user by device_user_id field (you may need to add this field to users table)
        $user = User::where('device_user_id', $deviceUserId)->first();

        if (!$user) {
            // Alternative: try to find by employee_id or other identifier
            $user = User::where('employee_id', $deviceUserId)->first();
        }

        return $user;
    }

    /**
     * Determine punch type based on log data
     */
    private function determinePunchType($log): string
    {
        // This is a basic implementation - you may need to adjust based on your device configuration
        $status = $log['status'] ?? 0;

        switch ($status) {
            case 0:
                return 'check_in';
            case 1:
                return 'check_out';
            case 2:
                return 'break_out';
            case 3:
                return 'break_in';
            default:
                return 'check_in';
        }
    }

    /**
     * Get verification type from device data
     */
    private function getVerificationType($type): ?string
    {
        switch ($type) {
            case 1:
                return 'fingerprint';
            case 15:
                return 'face';
            case 2:
                return 'password';
            case 3:
                return 'card';
            default:
                return 'unknown';
        }
    }

    /**
     * Get device information
     */
    public function getDeviceInfo(): array
    {
        if (!$this->connect()) {
            return [];
        }

        try {
            // Use the helper classes to get device information
            $info = [
                'device_ip' => $this->deviceIp,
                'device_port' => $this->devicePort,
                'device_time' => $this->zk->getTime(),
                'user_count' => count($this->zk->getUser()),
                'attendance_count' => count($this->zk->getAttendance()),
            ];

            // Try to get additional device info using helper classes
            try {
                $platform = \Rats\Zkteco\Lib\Helper\Platform::get($this->zk);
                if ($platform && !empty(trim($platform))) {
                    $info['platform'] = $this->sanitizeString($platform);
                } else {
                    $info['platform'] = 'ZKTeco Device';
                }
            } catch (Exception $e) {
                Log::error("Error getting platform: " . $e->getMessage());
                $info['platform'] = 'ZKTeco Device';
            }

            try {
                $version = \Rats\Zkteco\Lib\Helper\Version::get($this->zk);
                if ($version && !empty(trim($version))) {
                    $info['firmware_version'] = $this->sanitizeString($version);
                } else {
                    $info['firmware_version'] = 'Standard Firmware';
                }
            } catch (Exception $e) {
                Log::error("Error getting version: " . $e->getMessage());
                $info['firmware_version'] = 'Standard Firmware';
            }

            try {
                $serial = \Rats\Zkteco\Lib\Helper\SerialNumber::get($this->zk);
                if ($serial && !empty(trim($serial))) {
                    $info['serial_number'] = $this->sanitizeString($serial);
                } else {
                    $info['serial_number'] = 'Device-' . str_replace('.', '-', $this->deviceIp);
                }
            } catch (Exception $e) {
                Log::error("Error getting serial number: " . $e->getMessage());
                $info['serial_number'] = 'Device-' . str_replace('.', '-', $this->deviceIp);
            }

            $this->disconnect();
            return $info;
        } catch (Exception $e) {
            Log::error("Error getting device info: " . $e->getMessage());
            $this->disconnect();
            return [];
        }
    }

    /**
     * Clear attendance logs from device
     */
    public function clearAttendanceLogs(): bool
    {
        if (!$this->connect()) {
            return false;
        }

        try {
            $result = $this->zk->clearAttendance();
            $this->disconnect();
            return $result;
        } catch (Exception $e) {
            Log::error("Error clearing attendance logs: " . $e->getMessage());
            $this->disconnect();
            return false;
        }
    }

    /**
     * Sanitize string to ensure valid UTF-8 encoding for JSON
     */
    private function sanitizeString($string): string
    {
        if (!is_string($string)) {
            return (string) $string;
        }

        // Remove or replace invalid UTF-8 characters
        $sanitized = mb_convert_encoding($string, 'UTF-8', 'UTF-8');

        // Remove null bytes and other problematic characters
        $sanitized = str_replace(["\0", "\x00"], '', $sanitized);

        // Ensure it's valid UTF-8
        if (!mb_check_encoding($sanitized, 'UTF-8')) {
            $sanitized = utf8_encode($sanitized);
        }

        return trim($sanitized);
    }

    /**
     * Test device connection
     */
    public function testConnection(): array
    {
        $startTime = microtime(true);
        $connected = $this->connect();
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);

        if ($connected) {
            $this->disconnect();
            return [
                'status' => 'success',
                'message' => 'Device connected successfully',
                'response_time' => $responseTime . 'ms',
                'device_ip' => $this->deviceIp,
                'device_port' => $this->devicePort
            ];
        }

        return [
            'status' => 'error',
            'message' => 'Failed to connect to device',
            'response_time' => $responseTime . 'ms',
            'device_ip' => $this->deviceIp,
            'device_port' => $this->devicePort
        ];
    }
}
