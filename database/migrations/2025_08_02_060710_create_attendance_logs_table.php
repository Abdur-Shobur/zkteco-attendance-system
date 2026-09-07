<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('device_user_id'); // User ID from biometric device
            $table->string('device_ip');
            $table->timestamp('punch_time');
            $table->enum('punch_type', ['check_in', 'check_out', 'break_in', 'break_out']);
            $table->string('verification_type')->nullable(); // fingerprint, face, card, etc.
            $table->integer('work_code')->nullable();
            $table->boolean('is_processed')->default(false);
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['device_user_id', 'punch_time']);
            $table->index('device_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
