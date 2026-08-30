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
            $table->renameColumn('from_email', 'from_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospect_actions', function (Blueprint $table) {
            $table->renameColumn('from_label', 'from_email');
        });
    }
};
