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
        Schema::table('task_links', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('task_id');
        });

        // Backfill so existing links keep their current (id) order instead of
        // all landing on 0 and shuffling arbitrarily the first time they load.
        DB::table('task_links')->select('id', 'task_id')->orderBy('task_id')->orderBy('id')
            ->get()
            ->groupBy('task_id')
            ->each(function ($links) {
                foreach ($links->values() as $index => $link) {
                    DB::table('task_links')->where('id', $link->id)->update(['sort_order' => $index]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_links', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
