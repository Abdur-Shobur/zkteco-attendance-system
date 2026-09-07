<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\User;
use App\Services\AdmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AdmsController extends Controller
{
    public function __construct(private AdmsService $admsService)
    {
    }
    /**
     * Device handshake + attendance/data push endpoint.
     * ZKTeco ADMS/iClock protocol: /iclock/cdata
     */
    public function cdata(Request $request): Response
    {
        $sn = (string) $request->query('SN', $request->query('sn', 'unknown'));
        $this->touchDevice($sn, $request);

        // Initial handshake: device asks for options
        if ($request->isMethod('get') && ($request->has('options') || $request->query('options') === 'all')) {
            Log::channel(config('zkteco.logging.channel', 'daily'))
                ->info('ADMS handshake', ['sn' => $sn, 'ip' => $request->ip()]);

            $body = implode("\n", [
                "GET OPTION FROM: {$sn}",
                'Stamp=' . Cache::get($this->stampKey($sn, 'ATTLOG'), 0),
                'OpStamp=' . Cache::get($this->stampKey($sn, 'OPERLOG'), 0),
                'PhotoStamp=' . Cache::get($this->stampKey($sn, 'ATTPHOTO'), 0),
                'ATTLOGStamp=' . Cache::get($this->stampKey($sn, 'ATTLOG'), 0),
                'OPERLOGStamp=' . Cache::get($this->stampKey($sn, 'OPERLOG'), 0),
                'BIODATAStamp=' . Cache::get($this->stampKey($sn, 'BIODATA'), 0),
                'ErrorDelay=30',
                'Delay=10',
                'TransTimes=00:00;14:00',
                'TransInterval=1',
                'TransFlag=111111111111',
                'TimeZone=' . (int) config('zkteco.adms.timezone', 6),
                'Realtime=1',
                'Encrypt=0',
                'ServerVer=3.0.1',
                'PushProtVer=2.4.1',
                'SupportPing=1',
            ]);

            return $this->plain($body);
        }

        // Push of attendance / other tables
        if ($request->isMethod('post')) {
            $table = strtoupper((string) $request->query('table', ''));
            $stamp = $request->query('Stamp', $request->query('stamp'));
            $raw = $request->getContent();

            Log::channel(config('zkteco.logging.channel', 'daily'))
                ->info('ADMS data push', [
                    'sn' => $sn,
                    'table' => $table,
                    'stamp' => $stamp,
                    'bytes' => strlen($raw),
                    'ip' => $request->ip(),
                ]);

            $count = 0;
            if ($table === 'ATTLOG' || $table === '') {
                $count = $this->storeAttLogs($raw, $request->ip(), $sn);
            } elseif (in_array($table, ['USERINFO', 'USER', 'OPERLOG'], true)) {
                $count = $this->admsService->storeUserInfo($raw, $sn);
            } elseif ($table === 'BIODATA') {
                // Biometric templates only — acknowledge, no user names here
                Log::info('ADMS BIODATA received', ['sn' => $sn, 'bytes' => strlen($raw)]);
            }

            if ($stamp !== null && $stamp !== '') {
                Cache::forever($this->stampKey($sn, $table !== '' ? $table : 'ATTLOG'), $stamp);
            }

            // Device expects OK or OK:N
            return $this->plain($count > 0 ? "OK:{$count}" : 'OK');
        }

        return $this->plain('OK');
    }

    /**
     * Device polls for pending commands.
     */
    public function getRequest(Request $request): Response
    {
        $sn = (string) $request->query('SN', $request->query('sn', 'unknown'));
        $this->touchDevice($sn, $request);

        $command = $this->admsService->popCommand($sn);
        if ($command) {
            Log::channel(config('zkteco.logging.channel', 'daily'))
                ->info('ADMS command sent', ['sn' => $sn, 'command' => $command]);
            return $this->plain($command);
        }

        Log::channel(config('zkteco.logging.channel', 'daily'))
            ->debug('ADMS getrequest', ['sn' => $sn, 'ip' => $request->ip()]);

        return $this->plain('OK');
    }

    /**
     * Optional command ACK endpoint used by some firmwares.
     */
    public function deviceCmd(Request $request): Response
    {
        $sn = (string) $request->query('SN', $request->query('sn', 'unknown'));
        $this->touchDevice($sn, $request);

        return $this->plain('OK');
    }

    /**
     * ADMS status for dashboard "Test Connection".
     */
    public function status(): array
    {
        $devices = Cache::get('adms.devices', []);
        $onlineWindow = (int) config('zkteco.adms.online_window_seconds', 120);
        $online = [];

        foreach ($devices as $sn => $meta) {
            $lastSeen = isset($meta['last_seen']) ? Carbon::parse($meta['last_seen']) : null;
            $isOnline = $lastSeen && $lastSeen->greaterThan(now()->subSeconds($onlineWindow));
            $online[] = [
                'sn' => $sn,
                'ip' => $meta['ip'] ?? null,
                'last_seen' => $meta['last_seen'] ?? null,
                'online' => $isOnline,
            ];
        }

        $anyOnline = collect($online)->contains(fn ($d) => $d['online']);

        return [
            'status' => $anyOnline ? 'success' : 'error',
            'mode' => 'adms',
            'message' => $anyOnline
                ? 'Device contacted ADMS server recently'
                : 'Waiting for device to contact ADMS server (check Cloud Server IP/port and Windows Firewall for 8081)',
            'devices' => $online,
            'server' => [
                'listen_hint' => 'http://192.168.68.56:8081',
                'endpoints' => [
                    '/iclock/cdata',
                    '/iclock/getrequest',
                ],
            ],
        ];
    }

    private function storeAttLogs(string $raw, ?string $deviceIp, string $sn): int
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($raw)) ?: [];
        $count = 0;
        $deviceIp = $deviceIp ?: config('zkteco.device_ip');

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // PIN TIME STATUS VERIFY WORKCODE ...
            $parts = preg_split('/\s+/', $line);
            if (count($parts) < 2) {
                continue;
            }

            $deviceUserId = (string) $parts[0];
            $punchTimeRaw = $parts[1];
            // Sometimes time is "YYYY-MM-DD" + "HH:MM:SS" as two fields
            if (isset($parts[2]) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $parts[2])) {
                $punchTimeRaw .= ' ' . $parts[2];
                $status = isset($parts[3]) ? (int) $parts[3] : 0;
                $verify = isset($parts[4]) ? (int) $parts[4] : null;
                $workCode = isset($parts[5]) ? (int) $parts[5] : null;
            } else {
                $status = isset($parts[2]) ? (int) $parts[2] : 0;
                $verify = isset($parts[3]) ? (int) $parts[3] : null;
                $workCode = isset($parts[4]) ? (int) $parts[4] : null;
            }

            try {
                $punchTime = Carbon::parse($punchTimeRaw);
            } catch (\Throwable $e) {
                Log::warning('ADMS skipped invalid punch time', ['line' => $line]);
                continue;
            }

            $exists = AttendanceLog::where('device_user_id', $deviceUserId)
                ->where('punch_time', $punchTime)
                ->where('device_ip', $deviceIp)
                ->exists();

            if ($exists) {
                continue;
            }

            $user = User::where('device_user_id', $deviceUserId)->first()
                ?: User::where('employee_id', $deviceUserId)->first();

            AttendanceLog::create([
                'user_id' => $user?->id,
                'device_user_id' => $deviceUserId,
                'device_ip' => $deviceIp,
                'punch_time' => $punchTime,
                'punch_type' => $this->mapPunchType($status),
                'verification_type' => $this->mapVerificationType($verify),
                'work_code' => $workCode,
                'is_processed' => false,
            ]);

            $count++;
        }

        if ($count > 0) {
            Log::info("ADMS stored {$count} attendance log(s)", ['sn' => $sn]);
        }

        return $count;
    }

    private function mapPunchType(int $status): string
    {
        return match ($status) {
            1 => 'check_out',
            2 => 'break_out',
            3 => 'break_in',
            default => 'check_in',
        };
    }

    private function mapVerificationType(?int $type): ?string
    {
        return match ($type) {
            1 => 'fingerprint',
            2 => 'password',
            3 => 'card',
            4 => 'combination',
            15 => 'face',
            16 => 'vein',
            17 => 'palm',
            default => $type !== null ? (string) $type : null,
        };
    }

    private function touchDevice(string $sn, Request $request): void
    {
        $devices = Cache::get('adms.devices', []);
        $devices[$sn] = [
            'ip' => $request->ip(),
            'last_seen' => now()->toDateTimeString(),
            'user_agent' => $request->userAgent(),
        ];
        Cache::forever('adms.devices', $devices);
        Cache::put('adms.last_seen', now()->toDateTimeString(), now()->addDay());
    }

    private function stampKey(string $sn, string $table): string
    {
        return 'adms.stamp.' . $sn . '.' . strtoupper($table);
    }

    private function plain(string $body): Response
    {
        return response($body, 200)
            ->header('Content-Type', 'text/plain');
    }
}
