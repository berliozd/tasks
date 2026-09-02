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
            $table->integer('sort_order')->default(0)->after('parent_task_id');
        });

        // Backfill so existing tasks keep their current (id) order instead of
        // all landing on 0 and shuffling arbitrarily the first time they load.
        DB::table('tasks')->select('id', 'user_id')->orderBy('user_id')->orderBy('id')
            ->get()
            ->groupBy('user_id')
            ->each(function ($tasks) {
                foreach ($tasks->values() as $index => $task) {
                    DB::table('tasks')->where('id', $task->id)->update(['sort_order' => $index]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
