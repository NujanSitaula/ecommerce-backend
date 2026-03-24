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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit'); // e.g., "yards", "pieces", "kg"
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->decimal('low_stock_threshold', 10, 2)->default(0);
            $table->decimal('cost_per_unit', 10, 2)->default(0);
            $table->string('supplier')->nullable();
            $table->timestamps();
            
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
