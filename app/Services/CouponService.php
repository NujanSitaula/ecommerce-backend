<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function findAndValidate(string $code, User $user, float $cartTotal): Coupon
    {
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon || !$coupon->is_active) {
            throw ValidationException::withMessages([
                'code' => ['This coupon code is invalid.'],
            ]);
        }

        $now = Carbon::now();

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            throw ValidationException::withMessages([
                'code' => ['This coupon is not active yet.'],
            ]);
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => ['This coupon has expired.'],
            ]);
        }

        if ($coupon->min_cart_total && $cartTotal < (float) $coupon->min_cart_total) {
            throw ValidationException::withMessages([
                'code' => ['Cart total is too low for this coupon.'],
            ]);
        }

        // Global usage limit
        if ($coupon->max_uses !== null) {
            $globalUsed = CouponRedemption::where('coupon_id', $coupon->id)->count();
            if ($globalUsed >= $coupon->max_uses) {
                throw ValidationException::withMessages([
                    'code' => ['This coupon has reached its maximum number of uses.'],
                ]);
            }
        }

        // Per-user usage limit
        if ($coupon->max_uses_per_user !== null) {
            $userUsed = CouponRedemption::where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->count();

            if ($userUsed >= $coupon->max_uses_per_user) {
                throw ValidationException::withMessages([
                    'code' => ['You have already used this coupon the maximum number of times.'],
                ]);
            }
        }

        return $coupon;
    }

    /**
     * @return array{discount_amount: float, shipping_discount: float}
     */
    public function calculateDiscount(Coupon $coupon, float $cartSubtotal, float $shippingFee): array
    {
        $discountAmount = 0.0;
        $shippingDiscount = 0.0;

        switch ($coupon->type) {
            case 'free_shipping':
                $shippingDiscount = $shippingFee;
                break;
            case 'percent':
                $value = (float) ($coupon->value ?? 0);
                $discountAmount = $cartSubtotal * ($value / 100);
                // Apply max discount cap if set
                if ($coupon->max_discount_amount !== null) {
                    $maxDiscount = (float) $coupon->max_discount_amount;
                    $discountAmount = min($discountAmount, $maxDiscount);
                }
                break;
            case 'flat':
                $value = (float) ($coupon->value ?? 0);
                $discountAmount = min($value, $cartSubtotal);
                break;
        }

        // Never exceed cart subtotal or shipping fee
        $discountAmount = max(0.0, min($discountAmount, $cartSubtotal));
        $shippingDiscount = max(0.0, min($shippingDiscount, $shippingFee));

        return [
            'discount_amount' => $discountAmount,
            'shipping_discount' => $shippingDiscount,
        ];
    }

    public function recordRedemption(Coupon $coupon, User $user, Order $order): void
    {
        CouponRedemption::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'used_at' => Carbon::now(),
        ]);
    }
}


