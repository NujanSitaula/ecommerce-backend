<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;
use Carbon\Carbon;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // 10% off coupon (example: 30% up to $100)
        Coupon::updateOrCreate(
            ['code' => 'TENPERCENT'],
            [
                'type' => 'percent',
                'value' => 10, // 10%
                'max_discount_amount' => null, // No cap for this example, but you can set to 100 for "10% up to $100"
                'max_uses' => null,
                'max_uses_per_user' => null,
                'min_cart_total' => 0,
                'starts_at' => $now,
                'expires_at' => null,
                'is_active' => true,
            ],
        );

        // Example: 30% off up to $100 coupon
        Coupon::updateOrCreate(
            ['code' => 'THIRTYPERCENT'],
            [
                'type' => 'percent',
                'value' => 30, // 30%
                'max_discount_amount' => 100, // Maximum discount is $100
                'max_uses' => null,
                'max_uses_per_user' => null,
                'min_cart_total' => 0,
                'starts_at' => $now,
                'expires_at' => null,
                'is_active' => true,
            ],
        );

        // $10 flat discount coupon
        Coupon::updateOrCreate(
            ['code' => 'TENOFF'],
            [
                'type' => 'flat',
                'value' => 10, // $10
                'max_uses' => null,
                'max_uses_per_user' => null,
                'min_cart_total' => 0,
                'starts_at' => $now,
                'expires_at' => null,
                'is_active' => true,
            ],
        );

        // Free shipping coupon
        Coupon::updateOrCreate(
            ['code' => 'FREESHIP'],
            [
                'type' => 'free_shipping',
                'value' => null,
                'max_uses' => null,
                'max_uses_per_user' => null,
                'min_cart_total' => 0,
                'starts_at' => $now,
                'expires_at' => null,
                'is_active' => true,
            ],
        );
    }
}
