<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Colors identifying each product on the "Actions completed over time"
     * chart. Same palette used for the default assigned to new products —
     * see ProductService::nextColor().
     */
    private const PALETTE = [
        '#158749', '#2563eb', '#d97706', '#dc2626', '#7c3aed',
        '#0891b2', '#db2777', '#65a30d', '#ea580c', '#4338ca',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('color', 7)->nullable()->after('brief');
        });

        DB::table('products')->orderBy('team_id')->orderBy('id')->get(['id', 'team_id'])
            ->groupBy('team_id')
            ->each(function ($products) {
                foreach ($products->values() as $index => $product) {
                    DB::table('products')->where('id', $product->id)
                        ->update(['color' => self::PALETTE[$index % count(self::PALETTE)]]);
                }
            });

        Schema::table('products', function (Blueprint $table) {
            $table->string('color', 7)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
