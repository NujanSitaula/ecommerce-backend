<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('inventory_type', ['in_stock', 'made_to_order', 'both'])->default('in_stock')->after('quantity');
            $table->integer('production_time_days')->nullable()->after('inventory_type');
            $table->integer('min_quantity')->nullable()->after('production_time_days');
            $table->integer('max_quantity')->nullable()->after('min_quantity');
            $table->integer('low_stock_threshold')->default(5)->after('max_quantity');
            $table->boolean('track_inventory')->default(true)->after('low_stock_threshold');
            $table->decimal('cost_of_goods', 10, 2)->nullable()->after('track_inventory');
            $table->json('materials_required')->nullable()->after('cost_of_goods');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'inventory_type',
                'production_time_days',
                'min_quantity',
                'max_quantity',
                'low_stock_threshold',
                'track_inventory',
                'cost_of_goods',
                'materials_required',
            ]);
        });
    }
};
