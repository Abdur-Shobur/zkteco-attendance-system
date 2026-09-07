<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AttendanceLog;
use App\Models\User;
use Carbon\Carbon;

class AttendanceLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $deviceIp = '192.168.1.201';
        
        // Generate attendance logs for the past 7 days
        for ($day = 6; $day >= 0; $day--) {
            $date = Carbon::now()->subDays($day);
            
            // Skip weekends for this example
            if ($date->isWeekend()) {
                continue;
            }
            
            foreach ($users as $user) {
                // Skip some users randomly to simulate real attendance
                if (rand(1, 10) <= 2) {
                    continue;
                }
                
                // Morning check-in (8:00 AM - 9:30 AM)
                $checkInTime = $date->copy()
                    ->setHour(8)
                    ->setMinute(0)
                    ->addMinutes(rand(0, 90));
                
                AttendanceLog::create([
                    'user_id' => $user->id,
                    'device_user_id' => $user->device_user_id,
                    'device_ip' => $deviceIp,
                    'punch_time' => $checkInTime,
                    'punch_type' => 'check_in',
                    'verification_type' => $this->getRandomVerificationType(),
                    'work_code' => '0',
                    'is_processed' => rand(0, 1),
                ]);
                
                // Lunch break out (12:00 PM - 1:00 PM)
                if (rand(1, 10) <= 7) { // 70% chance of lunch break
                    $lunchOutTime = $date->copy()
                        ->setHour(12)
                        ->setMinute(0)
                        ->addMinutes(rand(0, 60));
                    
                    AttendanceLog::create([
                        'user_id' => $user->id,
                        'device_user_id' => $user->device_user_id,
                        'device_ip' => $deviceIp,
                        'punch_time' => $lunchOutTime,
                        'punch_type' => 'break_out',
                        'verification_type' => $this->getRandomVerificationType(),
                        'work_code' => '0',
                        'is_processed' => rand(0, 1),
                    ]);
                    
                    // Lunch break in (1:00 PM - 2:00 PM)
                    $lunchInTime = $lunchOutTime->copy()->addMinutes(rand(30, 90));
                    
                    AttendanceLog::create([
                        'user_id' => $user->id,
                        'device_user_id' => $user->device_user_id,
                        'device_ip' => $deviceIp,
                        'punch_time' => $lunchInTime,
                        'punch_type' => 'break_in',
                        'verification_type' => $this->getRandomVerificationType(),
                        'work_code' => '0',
                        'is_processed' => rand(0, 1),
                    ]);
                }
                
                // Evening check-out (5:00 PM - 7:00 PM)
                $checkOutTime = $date->copy()
                    ->setHour(17)
                    ->setMinute(0)
                    ->addMinutes(rand(0, 120));
                
                AttendanceLog::create([
                    'user_id' => $user->id,
                    'device_user_id' => $user->device_user_id,
                    'device_ip' => $deviceIp,
                    'punch_time' => $checkOutTime,
                    'punch_type' => 'check_out',
                    'verification_type' => $this->getRandomVerificationType(),
                    'work_code' => '0',
                    'is_processed' => rand(0, 1),
                ]);
            }
        }
    }
    
    /**
     * Get random verification type
     */
    private function getRandomVerificationType(): string
    {
        $types = ['fingerprint', 'face', 'card', 'password'];
        return $types[array_rand($types)];
    }
}
