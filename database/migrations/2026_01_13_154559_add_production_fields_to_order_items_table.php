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
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('variant_id')->nullable()->after('product_id')->constrained('product_variants')->nullOnDelete();
            $table->boolean('is_made_to_order')->default(false)->after('subtotal');
            $table->enum('production_status', ['pending', 'in_progress', 'completed', 'cancelled'])->nullable()->after('is_made_to_order');
            $table->timestamp('production_started_at')->nullable()->after('production_status');
            $table->timestamp('production_completed_at')->nullable()->after('production_started_at');
            $table->date('estimated_completion_date')->nullable()->after('production_completed_at');
            
            $table->index('production_status');
            $table->index('estimated_completion_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);
            $table->dropIndex(['production_status']);
            $table->dropIndex(['estimated_completion_date']);
            $table->dropColumn([
                'variant_id',
                'is_made_to_order',
                'production_status',
                'production_started_at',
                'production_completed_at',
                'estimated_completion_date',
            ]);
        });
    }
};
