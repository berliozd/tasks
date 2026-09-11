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
        Schema::table('needs', function (Blueprint $table) {
            // Nullable for now — backfilled from the old `stage` string in
            // the next migration, then made non-nullable once every row has
            // a value.
            $table->foreignId('need_stage_id')->nullable()->after('stage')->constrained('need_stages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('needs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('need_stage_id');
        });
    }
};
