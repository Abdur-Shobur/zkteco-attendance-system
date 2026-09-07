<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@company.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'device_user_id' => '1',
            'employee_id' => 'EMP001',
        ]);

        // Create sample employees with device mapping
        $employees = [
            [
                'name' => 'John Doe',
                'email' => 'john.doe@company.com',
                'device_user_id' => '2',
                'employee_id' => 'EMP002',
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane.smith@company.com',
                'device_user_id' => '3',
                'employee_id' => 'EMP003',
            ],
            [
                'name' => 'Mike Johnson',
                'email' => 'mike.johnson@company.com',
                'device_user_id' => '4',
                'employee_id' => 'EMP004',
            ],
            [
                'name' => 'Sarah Wilson',
                'email' => 'sarah.wilson@company.com',
                'device_user_id' => '5',
                'employee_id' => 'EMP005',
            ],
            [
                'name' => 'David Brown',
                'email' => 'david.brown@company.com',
                'device_user_id' => '6',
                'employee_id' => 'EMP006',
            ],
        ];

        foreach ($employees as $employee) {
            User::create([
                'name' => $employee['name'],
                'email' => $employee['email'],
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'device_user_id' => $employee['device_user_id'],
                'employee_id' => $employee['employee_id'],
            ]);
        }
    }
}
