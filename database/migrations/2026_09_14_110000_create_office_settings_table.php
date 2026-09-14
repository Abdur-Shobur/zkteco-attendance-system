<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_settings', function (Blueprint $table) {
            $table->id();
            $table->string('work_start', 5)->default('09:00');
            $table->string('work_end', 5)->default('18:00');
            $table->unsignedSmallInteger('late_grace_minutes')->default(15);
            $table->json('working_days')->nullable(); // [1,2,3,4,5] Mon-Fri
            $table->json('holidays')->nullable(); // ["2026-12-16", ...]
            $table->timestamps();
        });

        DB::table('office_settings')->insert([
            'work_start' => config('attendance.work_start', '09:00'),
            'work_end' => config('attendance.work_end', '18:00'),
            'late_grace_minutes' => (int) config('attendance.late_grace_minutes', 15),
            'working_days' => json_encode(config('attendance.working_days', [1, 2, 3, 4, 5])),
            'holidays' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('office_settings');
    }
};
