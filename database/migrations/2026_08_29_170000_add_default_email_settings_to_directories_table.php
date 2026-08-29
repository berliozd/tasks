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
        Schema::table('directories', function (Blueprint $table) {
            $table->string('default_from_email')->nullable()->after('prompt');
            $table->string('default_reply_to_email')->nullable()->after('default_from_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('directories', function (Blueprint $table) {
            $table->dropColumn(['default_from_email', 'default_reply_to_email']);
        });
    }
};
