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
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('recurrence_id')
                ->nullable()
                ->after('scheduled_at')
                ->constrained('recurrences');
        });

        $codeToId = DB::table('recurrences')->pluck('id', 'code');
        foreach ($codeToId as $code => $id) {
            DB::table('tasks')
                ->where('recurrence', $code)
                ->update(['recurrence_id' => $id]);
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('recurrence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('recurrence')->nullable()->after('scheduled_at');
        });

        $idToCode = DB::table('recurrences')->pluck('code', 'id');
        foreach ($idToCode as $id => $code) {
            DB::table('tasks')
                ->where('recurrence_id', $id)
                ->update(['recurrence' => $code]);
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recurrence_id');
        });
    }
};

