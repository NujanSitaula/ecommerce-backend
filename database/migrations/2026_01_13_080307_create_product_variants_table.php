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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku')->nullable()->unique();
            $table->json('attributes'); // e.g., {"size": "L", "color": "Red"}
            $table->decimal('price', 10, 2)->nullable(); // Override base price if set
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->integer('quantity')->default(0);
            $table->string('image_url')->nullable(); // Variant-specific image
            $table->timestamps();
            
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
