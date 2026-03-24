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
            $table->decimal('tax_amount', 10, 2)->default(0)->after('shipping_discount');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('tax_amount');
            $table->string('tax_type', 32)->nullable()->after('tax_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['tax_amount', 'tax_rate', 'tax_type']);
        });
    }
};
