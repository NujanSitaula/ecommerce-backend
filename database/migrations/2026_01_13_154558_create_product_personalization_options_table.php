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
        Schema::create('product_personalization_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name'); // e.g., "Custom Text", "Color Choice"
            $table->enum('type', ['text', 'number', 'select', 'color', 'file_upload', 'checkbox']);
            $table->boolean('required')->default(false);
            $table->json('options')->nullable(); // For select/color types
            $table->integer('max_length')->nullable(); // For text type
            $table->decimal('price_adjustment', 10, 2)->nullable(); // Additional cost
            $table->integer('order')->default(0); // Display order
            $table->timestamps();
            
            $table->index(['product_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_personalization_options');
    }
};
