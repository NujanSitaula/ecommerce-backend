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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->integer('quantity')->default(0);
            $table->string('unit')->nullable();
            $table->string('type')->nullable(); // bag, pashmina, souvenir
            $table->boolean('featured')->default(false);
            $table->string('status')->default('active'); // active, draft
            $table->string('thumbnail_url')->nullable();
            $table->string('original_url')->nullable();
            $table->json('gallery')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};













