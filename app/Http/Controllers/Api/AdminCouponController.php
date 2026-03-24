<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class AdminCouponController extends Controller
{
    /**
     * List all coupons with optional search and pagination.
     */
    public function index(Request $request)
    {
        $query = Coupon::query()->withCount('redemptions');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('code', 'like', "%{$search}%");
        }

        $coupons = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 20));

        $items = collect($coupons->items())->map(function ($coupon) {
            return $this->formatCoupon($coupon);
        });

        return response()->json([
            'data' => $items,
            'current_page' => $coupons->currentPage(),
            'last_page' => $coupons->lastPage(),
            'per_page' => $coupons->perPage(),
            'total' => $coupons->total(),
        ]);
    }

    /**
     * Create a coupon.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:coupons,code',
            'type' => 'required|in:free_shipping,percent,flat',
            'value' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:0',
            'max_uses_per_user' => 'nullable|integer|min:0',
            'min_cart_total' => 'nullable|numeric|min:0',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        if (in_array($validated['type'], ['percent', 'flat']) && empty($validated['value'])) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['value' => ['The value field is required for percent and flat coupon types.']],
            ], 422);
        }

        $coupon = Coupon::create(array_merge($validated, [
            'is_active' => $validated['is_active'] ?? true,
        ]));

        return response()->json([
            'message' => 'Coupon created successfully',
            'data' => $this->formatCoupon($coupon->loadCount('redemptions')),
        ], 201);
    }

    /**
     * Get a single coupon.
     */
    public function show($id)
    {
        $coupon = Coupon::withCount('redemptions')->findOrFail($id);

        return response()->json([
            'data' => $this->formatCoupon($coupon),
        ]);
    }

    /**
     * Update a coupon.
     */
    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);

        $validated = $request->validate([
            'code' => 'sometimes|string|max:255|unique:coupons,code,' . $id,
            'type' => 'sometimes|in:free_shipping,percent,flat',
            'value' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:0',
            'max_uses_per_user' => 'nullable|integer|min:0',
            'min_cart_total' => 'nullable|numeric|min:0',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $type = $validated['type'] ?? $coupon->type;
        $value = $validated['value'] ?? $coupon->value;
        if (in_array($type, ['percent', 'flat']) && (empty($value) && $value !== 0)) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['value' => ['The value field is required for percent and flat coupon types.']],
            ], 422);
        }

        if (isset($validated['expires_at']) && isset($validated['starts_at']) && $validated['expires_at'] < $validated['starts_at']) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['expires_at' => ['Expires at must be after or equal to starts at.']],
            ], 422);
        }

        $coupon->update($validated);

        return response()->json([
            'message' => 'Coupon updated successfully',
            'data' => $this->formatCoupon($coupon->fresh()->loadCount('redemptions')),
        ]);
    }

    /**
     * Delete a coupon.
     */
    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return response()->json([
            'message' => 'Coupon deleted successfully',
        ]);
    }

    /**
     * Format coupon for API response.
     */
    private function formatCoupon(Coupon $coupon): array
    {
        return [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => $coupon->value ? (float) $coupon->value : null,
            'max_discount_amount' => $coupon->max_discount_amount ? (float) $coupon->max_discount_amount : null,
            'max_uses' => $coupon->max_uses,
            'max_uses_per_user' => $coupon->max_uses_per_user,
            'min_cart_total' => $coupon->min_cart_total ? (float) $coupon->min_cart_total : null,
            'starts_at' => $coupon->starts_at?->toISOString(),
            'expires_at' => $coupon->expires_at?->toISOString(),
            'is_active' => (bool) $coupon->is_active,
            'redemptions_count' => $coupon->redemptions_count ?? $coupon->redemptions()->count(),
            'created_at' => $coupon->created_at?->toISOString(),
            'updated_at' => $coupon->updated_at?->toISOString(),
        ];
    }
}
