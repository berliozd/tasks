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
        Schema::table('prospects', function (Blueprint $table) {
            $table->boolean('won')->default(false)->after('email');
        });

        // "won" moves from being a loggable action status to a prospect flag;
        // carry over prospects that already had a "won" action logged.
        $wonProspectIds = DB::table('prospect_actions')->where('status', 'won')->pluck('prospect_id');
        DB::table('prospects')->whereIn('id', $wonProspectIds)->update(['won' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn('won');
        });
    }
};
