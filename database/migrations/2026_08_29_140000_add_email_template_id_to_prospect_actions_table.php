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
            // Nullable + nullOnDelete: deleting a template shouldn't erase the historical action log.
            $table->foreignId('email_template_id')->nullable()->after('prospect_id')
                ->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospect_actions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('email_template_id');
        });
    }
};
