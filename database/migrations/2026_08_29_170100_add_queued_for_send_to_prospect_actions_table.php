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
            $table->boolean('queued_for_send')->default(false)->after('status');
            $table->string('reply_to_email')->nullable()->after('from_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospect_actions', function (Blueprint $table) {
            $table->dropColumn(['queued_for_send', 'reply_to_email']);
        });
    }
};
