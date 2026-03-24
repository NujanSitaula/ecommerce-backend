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
        Schema::table('orders', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign(['user_id']);
            $table->dropForeign(['address_id']);
            $table->dropForeign(['contact_number_id']);
            $table->dropForeign(['payment_method_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            // Make user_id nullable for guest orders
            $table->foreignId('user_id')->nullable()->change();
            
            // Add guest fields
            $table->string('guest_email')->nullable()->after('user_id');
            $table->string('guest_name')->nullable()->after('guest_email');
            
            // Make address_id, contact_number_id, payment_method_id nullable for guest orders
            $table->foreignId('address_id')->nullable()->change();
            $table->foreignId('contact_number_id')->nullable()->change();
            $table->foreignId('payment_method_id')->nullable()->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            // Re-add foreign key constraints with onDelete('set null') for nullable columns
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('set null');
            $table->foreign('contact_number_id')->references('id')->on('contact_numbers')->onDelete('set null');
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Remove guest fields
            $table->dropColumn(['guest_email', 'guest_name']);
            
            // Note: Reverting nullable changes requires dropping and recreating foreign keys
            // This is complex and may cause data loss, so we'll leave them nullable
        });
    }
};
