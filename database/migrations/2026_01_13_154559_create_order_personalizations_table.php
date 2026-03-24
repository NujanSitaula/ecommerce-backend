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
        Schema::create('order_personalizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('personalization_option_id')->constrained('product_personalization_options')->cascadeOnDelete();
            $table->text('value'); // The actual personalization data
            $table->string('file_url')->nullable(); // For file uploads
            $table->timestamps();
            
            $table->index('order_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_personalizations');
    }
};
