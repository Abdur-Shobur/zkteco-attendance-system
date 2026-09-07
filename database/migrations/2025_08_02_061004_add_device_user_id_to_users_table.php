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
        Schema::table('users', function (Blueprint $table) {
            $table->string('device_user_id')->nullable()->unique()->after('email');
            $table->string('employee_id')->nullable()->unique()->after('device_user_id');
            $table->index('device_user_id');
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['device_user_id']);
            $table->dropIndex(['employee_id']);
            $table->dropColumn(['device_user_id', 'employee_id']);
        });
    }
};
