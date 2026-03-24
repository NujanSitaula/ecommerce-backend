<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CouponController extends Controller
{
    public function __construct(protected CouponService $couponService)
    {
    }

    public function apply(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string',
            'cart_total' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $coupon = $this->couponService->findAndValidate(
                $data['code'],
                $user,
                (float) $data['cart_total']
            );

            // For preview, we assume shipping fee = 0, actual calculation will happen in OrderController
            $discounts = $this->couponService->calculateDiscount(
                $coupon,
                (float) $data['cart_total'],
                0.0
            );

            return response()->json([
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => (float) ($coupon->value ?? 0),
                'discount_amount' => $discounts['discount_amount'],
                'shipping_discount' => $discounts['shipping_discount'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }
}


