<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class AdmsService
{
    public function listUsers(): array
    {
        $cached = array_values(Cache::get('adms.users', []));

        // Always include users already seen via attendance punches
        $fromLogs = AttendanceLog::query()
            ->select('device_user_id')
            ->distinct()
            ->orderBy('device_user_id')
            ->pluck('device_user_id');

        $byId = [];
        foreach ($cached as $user) {
            $id = (string) ($user['userid'] ?? '');
            if ($id !== '') {
                $byId[$id] = $user;
            }
        }

        foreach ($fromLogs as $deviceUserId) {
            $id = (string) $deviceUserId;
            if (!isset($byId[$id])) {
                $dbUser = User::where('device_user_id', $id)->orWhere('employee_id', $id)->first();
                $byId[$id] = [
                    'uid' => $id,
                    'userid' => $id,
                    'name' => $dbUser?->name ?? ('Device User ' . $id),
                    'role' => '0',
                    'password' => '',
                    'cardno' => '',
                    'source' => 'attendance_log',
                ];
            }
        }

        ksort($byId, SORT_NATURAL);

        return array_values($byId);
    }

    public function requestUserSync(?string $sn = null): array
    {
        $devices = Cache::get('adms.devices', []);
        $targets = $sn ? [$sn => ($devices[$sn] ?? [])] : $devices;

        $queued = [];
        foreach ($targets as $deviceSn => $meta) {
            if ($deviceSn === 'TESTDEVICE' || $deviceSn === '') {
                continue;
            }

            // Replace any pending commands with a single fresh query
            Cache::forever($this->commandKey($deviceSn), []);

            $cmdId = (int) Cache::increment('adms.cmd_seq.' . $deviceSn);
            // SpeedFace / iClock usually answers this with OPERLOG (+ BIODATA)
            $command = "C:{$cmdId}:DATA QUERY USERINFO";
            $this->queueCommand($deviceSn, $command);

            // Force re-upload of operation/user data on next options handshake
            Cache::forever($this->stampKey($deviceSn, 'OPERLOG'), 0);
            Cache::forever($this->stampKey($deviceSn, 'BIODATA'), 0);

            $queued[] = ['sn' => $deviceSn, 'command' => $command];
        }

        return [
            'queued' => $queued,
            'users' => $this->listUsers(),
            'message' => empty($queued)
                ? 'No ADMS device seen yet. Wait for device contact, then try again.'
                : 'User sync command queued. Device will push OPERLOG/USERINFO within about 1 minute.',
        ];
    }

    public function queueCommand(string $sn, string $command): void
    {
        $key = $this->commandKey($sn);
        $commands = Cache::get($key, []);
        $commands[] = $command;
        Cache::forever($key, $commands);
    }

    public function popCommand(string $sn): ?string
    {
        $key = $this->commandKey($sn);
        $commands = Cache::get($key, []);
        if (empty($commands)) {
            return null;
        }
        $command = array_shift($commands);
        Cache::forever($key, $commands);
        return $command;
    }

    public function storeUserInfo(string $raw, string $sn): int
    {
        // Keep last payload for debugging incomplete syncs
        try {
            Storage::disk('local')->put(
                'adms/last-operlog-' . $sn . '.txt',
                $raw
            );
        } catch (\Throwable $e) {
            // ignore storage failures
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($raw)) ?: [];
        $users = Cache::get('adms.users', []);
        $count = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // Skip pure operation logs / bio lines without user profile fields
            $upper = strtoupper($line);
            if (
                str_starts_with($upper, 'OPLOG')
                || str_starts_with($upper, 'FP ')
                || str_starts_with($upper, 'FACE ')
                || str_starts_with($upper, 'BIOPHOTO')
                || str_starts_with($upper, 'FVEIN')
            ) {
                continue;
            }

            $parsed = $this->parseUserLine($line);
            if (!$parsed) {
                Log::debug('ADMS skipped non-user line', ['sn' => $sn, 'line' => mb_substr($line, 0, 200)]);
                continue;
            }

            $users[(string) $parsed['userid']] = array_merge($parsed, [
                'sn' => $sn,
                'source' => 'userinfo',
                'updated_at' => now()->toDateTimeString(),
            ]);
            $count++;
        }

        Cache::forever('adms.users', $users);

        if ($count > 0) {
            Log::info("ADMS stored {$count} device user(s)", ['sn' => $sn, 'total_cached' => count($users)]);
            // Persist names into users table immediately
            $this->persistCachedUsersToDatabase($users);
        } else {
            Log::warning('ADMS OPERLOG/USERINFO contained no parseable users', [
                'sn' => $sn,
                'bytes' => strlen($raw),
                'preview' => mb_substr($raw, 0, 300),
            ]);
        }

        return $count;
    }

    public function syncUsersToDatabase(): array
    {
        $request = $this->requestUserSync();
        $deviceUsers = $this->listUsers();
        $result = $this->persistCachedUsersToDatabase(
            collect($deviceUsers)->keyBy('userid')->all()
        );

        return [
            'total_device_users' => count($deviceUsers),
            'synced_count' => $result['synced_count'],
            'updated_count' => $result['updated_count'],
            'errors' => $result['errors'],
            'adms_request' => $request['message'],
            'queued' => $request['queued'],
            'hint' => 'If count is still low, wait ~1 minute for OPERLOG push, then call sync again.',
        ];
    }

    private function persistCachedUsersToDatabase(array $users): array
    {
        $syncedCount = 0;
        $updatedCount = 0;
        $errors = [];

        foreach ($users as $deviceUser) {
            $userid = (string) ($deviceUser['userid'] ?? '');
            if ($userid === '') {
                continue;
            }

            try {
                $existingUser = User::where('device_user_id', $userid)->first();
                $name = trim((string) ($deviceUser['name'] ?? ''));
                if ($name === '') {
                    $name = 'Device User ' . $userid;
                }

                if ($existingUser) {
                    $existingUser->update([
                        'name' => $name,
                        'employee_id' => $userid,
                    ]);
                    $updatedCount++;
                } else {
                    $base = strtolower(preg_replace('/[^a-z0-9]+/i', '.', $name) ?: ('user' . $userid));
                    $base = trim($base, '.');
                    $email = $base . '@limerick.com';
                    if (User::where('email', $email)->exists()) {
                        $email = $base . rand(1000, 9999) . '@limerick.com';
                    }

                    User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => bcrypt('12345678'),
                        'device_user_id' => $userid,
                        'employee_id' => $userid,
                    ]);
                    $syncedCount++;
                }
            } catch (Exception $e) {
                $errors[] = "Error syncing user {$userid}: " . $e->getMessage();
                Log::error('ADMS user sync error: ' . $e->getMessage(), $deviceUser);
            }
        }

        return [
            'synced_count' => $syncedCount,
            'updated_count' => $updatedCount,
            'errors' => $errors,
        ];
    }

    private function parseUserLine(string $line): ?array
    {
        // OPERLOG often prefixes rows: "USER PIN=1\tName=John\t..."
        $line = preg_replace('/^(USER|USERINFO)\s+/i', '', trim($line)) ?? trim($line);

        // key=value format
        if (str_contains($line, '=')) {
            $fields = [];
            foreach (preg_split('/\t+/', $line) as $part) {
                $part = trim($part);
                if ($part === '' || !str_contains($part, '=')) {
                    continue;
                }
                [$k, $v] = explode('=', $part, 2);
                $key = strtoupper(trim($k));
                // Handle odd keys like "USER PIN"
                if (str_ends_with($key, ' PIN')) {
                    $key = 'PIN';
                }
                $fields[$key] = trim($v);
            }

            $pin = $fields['PIN'] ?? $fields['USERID'] ?? null;
            if ($pin === null || $pin === '') {
                return null;
            }

            return [
                'uid' => (string) $pin,
                'userid' => (string) $pin,
                'name' => $fields['NAME'] ?? '',
                'role' => $fields['PRI'] ?? $fields['PRIVILEGE'] ?? '0',
                'password' => $fields['PASSWD'] ?? $fields['PASSWORD'] ?? '',
                'cardno' => $fields['CARD'] ?? $fields['CARDNO'] ?? '',
            ];
        }

        // Tab/space separated: PIN Name Pri Passwd Card ...
        $parts = preg_split('/\t+/', $line);
        if (!$parts || count($parts) < 2) {
            $parts = preg_split('/\s{2,}/', $line) ?: [];
        }
        if (count($parts) < 1 || trim((string) $parts[0]) === '') {
            return null;
        }

        // Avoid treating random numeric oplog rows without a name as users
        if (count($parts) < 2) {
            return null;
        }

        return [
            'uid' => (string) $parts[0],
            'userid' => (string) $parts[0],
            'name' => $parts[1] ?? '',
            'role' => $parts[2] ?? '0',
            'password' => $parts[3] ?? '',
            'cardno' => $parts[4] ?? '',
        ];
    }

    private function commandKey(string $sn): string
    {
        return 'adms.commands.' . $sn;
    }

    private function stampKey(string $sn, string $table): string
    {
        return 'adms.stamp.' . $sn . '.' . strtoupper($table);
    }
}
