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
        Schema::create('need_stages', function (Blueprint $table) {
            $table->id();
            // Direct team_id (not just via the group) so permission checks
            // don't need a join.
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('need_stage_group_id')->constrained()->onDelete('cascade');
            $table->string('label');
            $table->string('color');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('need_stages');
    }
};
