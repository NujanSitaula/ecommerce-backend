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
            // SEO fields
            $table->string('meta_title')->nullable()->after('description');
            $table->text('meta_description')->nullable()->after('meta_title');
            
            // SKU
            $table->string('sku')->nullable()->unique()->after('slug');
            
            // Shipping fields
            $table->decimal('weight', 8, 2)->nullable()->after('quantity'); // in kg
            $table->decimal('length', 8, 2)->nullable()->after('weight'); // in cm
            $table->decimal('width', 8, 2)->nullable()->after('length'); // in cm
            $table->decimal('height', 8, 2)->nullable()->after('width'); // in cm
            $table->string('shipping_class')->nullable()->after('height');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'meta_title',
                'meta_description',
                'sku',
                'weight',
                'length',
                'width',
                'height',
                'shipping_class',
            ]);
        });
    }
};
