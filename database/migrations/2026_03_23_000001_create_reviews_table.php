<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Customer-friendly fields (denormalized for stability)
            $table->string('author_name');
            $table->string('author_email')->nullable();

            $table->unsignedTinyInteger('rating'); // 1..5
            $table->string('title')->nullable();
            $table->text('description');

            // Moderation lifecycle
            $table->enum('status', ['pending', 'approved', 'rejected', 'hidden'])->default('pending');
            $table->boolean('is_verified_purchase')->default(false);

            // Admin moderation metadata
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();

            $table->timestamps();

            // One review per user per product.
            $table->unique(['product_id', 'user_id']);

            $table->index(['product_id', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};

