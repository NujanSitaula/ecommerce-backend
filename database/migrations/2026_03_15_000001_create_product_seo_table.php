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
        Schema::create('product_seos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            // General SEO
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('meta_robots')->nullable();

            // Open Graph
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image_url')->nullable();
            $table->string('og_type')->nullable();
            $table->string('og_url_override')->nullable();

            // Twitter
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image_url')->nullable();
            $table->string('twitter_card_type')->nullable();

            // Evaluation
            $table->string('seo_status')->nullable(); // red, yellow, green
            $table->unsignedTinyInteger('seo_score')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_seos');
    }
};

