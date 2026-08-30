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
        Schema::table('directories', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('team_id')
                ->constrained()->onDelete('cascade');
        });

        // Backfill: every team that already has directories gets a default
        // product so existing directories keep working under the new
        // "directory belongs to a product" ownership model.
        DB::table('directories')->select('team_id')->distinct()->get()->each(function ($row) {
            $productId = DB::table('products')->insertGetId([
                'team_id' => $row->team_id,
                'name' => 'General',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('directories')->where('team_id', $row->team_id)->update(['product_id' => $productId]);
        });

        Schema::table('directories', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('directories', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }
};
