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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('address_id')->constrained('addresses')->cascadeOnDelete();
            $table->foreignId('contact_number_id')->constrained('contact_numbers')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->cascadeOnDelete();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->date('delivery_date');
            $table->boolean('gift_wrapped')->default(false);
            $table->text('delivery_instructions')->nullable();
            $table->boolean('leave_at_door')->default(false);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
