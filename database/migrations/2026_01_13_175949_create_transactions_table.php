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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->enum('type', ['payment', 'refund', 'refund_request', 'adjustment', 'order_modification'])->index();
            $table->enum('status', ['pending', 'approved', 'completed', 'rejected', 'failed'])->default('pending')->index();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_refund_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            
            $table->index(['order_id', 'type']);
            $table->index(['status', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
