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
        Schema::create('recurrences', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // daily|weekly|monthly|yearly
            $table->string('label');
            $table->timestamps();
        });

        DB::table('recurrences')->insert([
            ['code' => 'daily', 'label' => 'Daily', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'weekly', 'label' => 'Weekly', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'monthly', 'label' => 'Monthly', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'yearly', 'label' => 'Yearly', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurrences');
    }
};

