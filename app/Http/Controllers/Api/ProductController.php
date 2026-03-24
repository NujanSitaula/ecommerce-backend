<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->where('status', 'active');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($category = $request->query('category')) {
            $categories = is_string($category) ? explode(',', $category) : (array) $category;
            $query->whereHas('category', function ($q) use ($categories) {
                $q->whereIn('slug', $categories);
            });
        }

        $minPrice = $request->query('min_price');
        if ($minPrice !== null && is_numeric($minPrice)) {
            $query->whereRaw('COALESCE(sale_price, price) >= ?', [(float) $minPrice]);
        }

        $maxPrice = $request->query('max_price');
        if ($maxPrice !== null && is_numeric($maxPrice)) {
            $query->whereRaw('COALESCE(sale_price, price) <= ?', [(float) $maxPrice]);
        }

        // Sorting: default recommended (latest), support price asc/desc
        $sort = $request->query('sort');
        if ($sort === 'lowest') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'highest') {
            $query->orderBy('price', 'desc');
        } else {
            $query->latest();
        }

        // Pagination: allow pageSize param, default 20
        $perPage = (int) $request->query('pageSize', 20);
        $perPage = $perPage > 0 ? $perPage : 20;

        $products = $query->paginate($perPage);

        return ProductResource::collection($products);
    }

    public function popular()
    {
        $products = Product::where('status', 'active')
            ->orderByDesc('quantity')
            ->take(20)
            ->get();

        return ProductResource::collection($products);
    }

    public function bestSeller()
    {
        $products = Product::where('status', 'active')
            ->where('featured', true)
            ->take(20)
            ->get();

        return ProductResource::collection($products);
    }

    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $relations = [
            'category',
            'personalizationOptions',
            'variants',
        ];

        // If reviews table hasn't been migrated in the current DB yet,
        // don't crash the entire PDP. Rating/review_count will fall back to 0.
        if (Schema::hasTable('reviews')) {
            $relations[] = 'approvedReviews';
        }

        return new ProductResource($product->load($relations));
    }

    /**
     * Get same-category related products.
     * Falls back to other active products when category is empty or has no siblings.
     */
    public function related(Request $request, string $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $limit = (int) $request->query('limit', 12);
        $limit = $limit > 0 ? min($limit, 50) : 12;

        $products = collect();

        if ($product->category_id) {
            $products = Product::query()
                ->where('status', 'active')
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->latest()
                ->limit($limit)
                ->get();
        }

        // Fallback: if no same-category siblings, return other active products
        if ($products->isEmpty()) {
            $products = Product::query()
                ->where('status', 'active')
                ->where('id', '!=', $product->id)
                ->latest()
                ->limit($limit)
                ->get();
        }

        return ProductResource::collection($products);
    }
}




