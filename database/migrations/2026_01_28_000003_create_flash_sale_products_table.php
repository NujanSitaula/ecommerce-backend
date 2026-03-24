<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('flash_sale_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained('flash_sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['flash_sale_id', 'product_id']);
            $table->index('flash_sale_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_sale_products');
    }
};
