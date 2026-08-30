<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('prospect_actions')->where('status', 'planned')->update(['status' => 'pending']);

        Schema::table('prospect_actions', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('prospect_actions')->where('status', 'pending')->update(['status' => 'planned']);

        Schema::table('prospect_actions', function (Blueprint $table) {
            $table->string('status')->default('planned')->change();
        });
    }
};
