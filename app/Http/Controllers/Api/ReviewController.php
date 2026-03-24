<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    private const PURCHASE_ORDER_STATUSES = ['confirmed', 'processing', 'shipped', 'delivered'];

    /**
     * List approved reviews for a product.
     */
    public function index(Request $request, string $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $limit = (int) $request->query('limit', 10);
        $limit = $limit > 0 ? min($limit, 50) : 10;

        $reviews = Review::query()
            ->where('product_id', $product->id)
            ->where('status', 'approved')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return ReviewResource::collection($reviews);
    }

    /**
     * Submit a review (stored as pending, requires admin approval).
     */
    public function store(Request $request, string $slug)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $product = Product::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:2', 'max:5000'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $hasPurchased = OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->whereIn('status', self::PURCHASE_ORDER_STATUSES)
                    ->whereNull('cancelled_at');
            })
            ->exists();

        if (!$hasPurchased) {
            return response()->json([
                'message' => 'You must purchase this product to submit a review.',
            ], 422);
        }

        $existing = Review::query()
            ->where('product_id', $product->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'You have already submitted a review for this product.',
            ], 422);
        }

        $description = $validated['message'];
        $title = $validated['title'];
        if (empty($title)) {
            $stripped = trim(strip_tags($description));
            $title = mb_substr($stripped, 0, 60);
        }

        $authorName = $validated['name'] ?? $user->name ?? 'Customer';
        $authorEmail = $validated['email'] ?? $user->email ?? null;

        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'author_name' => $authorName,
            'author_email' => $authorEmail,
            'rating' => (int) $validated['rating'],
            'title' => $title,
            'description' => $description,
            'status' => 'pending',
            'is_verified_purchase' => true,
        ]);

        return (new ReviewResource($review))
            ->additional([
                'message' => 'Review submitted and is pending admin approval.',
                'status' => $review->status,
            ]);
    }
}

