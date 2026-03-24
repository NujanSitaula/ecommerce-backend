<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductSeo;
use App\Services\ProductSeoEvaluator;
use Illuminate\Http\Request;

class AdminProductSeoController extends Controller
{
    public function show(string $productId)
    {
        $product = Product::with(['seo', 'category'])->findOrFail($productId);

        return response()->json([
            'product' => new ProductResource($product->loadMissing('seo')),
            'seo' => $product->seo,
        ]);
    }

    public function update(Request $request, string $productId, ProductSeoEvaluator $evaluator)
    {
        $product = Product::with('seo')->findOrFail($productId);

        $validated = $request->validate([
            // General SEO
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
            'seo_keywords' => ['nullable', 'string', 'max:1000'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'meta_robots' => ['nullable', 'string', 'max:255'],

            // Open Graph
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string'],
            'og_image_url' => ['nullable', 'url', 'max:255'],
            'og_type' => ['nullable', 'string', 'max:50'],
            'og_url_override' => ['nullable', 'url', 'max:255'],

            // Twitter
            'twitter_title' => ['nullable', 'string', 'max:255'],
            'twitter_description' => ['nullable', 'string'],
            'twitter_image_url' => ['nullable', 'url', 'max:255'],
            'twitter_card_type' => ['nullable', 'string', 'max:50'],
        ]);

        $seo = $product->seo ?? new ProductSeo(['product_id' => $product->id]);
        $seo->fill($validated);

        // Evaluate and set status/score
        $seo = $evaluator->evaluate($product, $seo);
        $seo->save();

        $product->load('seo');

        return response()->json([
            'product' => new ProductResource($product),
            'seo' => $seo,
        ]);
    }
}

