<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('daily_report_enabled')->default(false)->after('timezone');
            // 0-23, the local hour (per the user's timezone column) to send at.
            $table->unsignedTinyInteger('daily_report_hour')->default(8)->after('daily_report_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['daily_report_enabled', 'daily_report_hour']);
        });
    }
};
