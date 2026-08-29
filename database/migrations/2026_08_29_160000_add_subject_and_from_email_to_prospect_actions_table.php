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
        Schema::table('prospect_actions', function (Blueprint $table) {
            $table->string('subject')->nullable()->after('type');
            $table->string('from_email')->nullable()->after('subject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospect_actions', function (Blueprint $table) {
            $table->dropColumn(['subject', 'from_email']);
        });
    }
};
