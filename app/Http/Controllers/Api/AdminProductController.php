<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductPersonalizationOption;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminProductController extends Controller
{
    /**
     * List all products (admin view - includes all statuses)
     */
    public function index(Request $request)
    {
        $query = Product::query()->with(['category', 'seo']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->query('perPage', 20);
        $perPage = $perPage > 0 ? $perPage : 20;

        $products = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => ProductResource::collection($products->items()),
            'total' => $products->total(),
            'page' => $products->currentPage(),
            'perPage' => $products->perPage(),
        ]);
    }

    /**
     * Get a single product by ID
     */
    public function show(string $id)
    {
        $product = Product::with(['category', 'variants', 'personalizationOptions', 'materials', 'seo'])->findOrFail($id);
        return new ProductResource($product);
    }

    /**
     * Create a new product
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:products,sku'],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'width' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'shipping_class' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:50'],
            'featured' => ['boolean'],
            'status' => ['required', Rule::in(['active', 'draft'])],
            'thumbnail_url' => ['nullable', 'url'],
            'original_url' => ['nullable', 'url'],
            'gallery' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'variants' => ['nullable', 'array'],
        ]);

        // Generate slug from name if not provided
        $slug = Str::slug($validated['name']);
        $uniqueSlug = $slug;
        $counter = 1;
        while (Product::where('slug', $uniqueSlug)->exists()) {
            $uniqueSlug = $slug . '-' . $counter;
            $counter++;
        }

        $variants = $validated['variants'] ?? null;
        $personalizationOptions = $validated['personalization_options'] ?? null;
        $materials = $validated['materials'] ?? null;
        unset($validated['variants'], $validated['personalization_options'], $validated['materials']);

        $product = Product::create([
            ...$validated,
            'slug' => $uniqueSlug,
        ]);

        // Create variants if provided
        if ($variants && is_array($variants)) {
            foreach ($variants as $variantData) {
                $variantValidated = validator($variantData, [
                    'sku' => ['nullable', 'string', 'max:255'],
                    'attributes' => ['required', 'array'],
                    'price' => ['nullable', 'numeric', 'min:0'],
                    'sale_price' => ['nullable', 'numeric', 'min:0'],
                    'quantity' => ['required', 'integer', 'min:0'],
                    'image_url' => ['nullable', 'url'],
                ])->validate();

                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $variantValidated['sku'] ?? null,
                    'attributes' => $variantValidated['attributes'],
                    'price' => $variantValidated['price'] ?? null,
                    'sale_price' => $variantValidated['sale_price'] ?? null,
                    'quantity' => $variantValidated['quantity'],
                    'image_url' => $variantValidated['image_url'] ?? null,
                ]);
            }
        }

        // Create personalization options if provided
        if ($personalizationOptions && is_array($personalizationOptions)) {
            foreach ($personalizationOptions as $index => $optionData) {
                $optionValidated = validator($optionData, [
                    'name' => ['required', 'string', 'max:255'],
                    'type' => ['required', Rule::in(['text', 'number', 'select', 'color', 'file_upload', 'checkbox'])],
                    'required' => ['boolean'],
                    'options' => ['nullable', 'array'],
                    'max_length' => ['nullable', 'integer', 'min:1'],
                    'price_adjustment' => ['nullable', 'numeric', 'min:0'],
                ])->validate();

                ProductPersonalizationOption::create([
                    'product_id' => $product->id,
                    'name' => $optionValidated['name'],
                    'type' => $optionValidated['type'],
                    'required' => $optionValidated['required'] ?? false,
                    'options' => $optionValidated['options'] ?? null,
                    'max_length' => $optionValidated['max_length'] ?? null,
                    'price_adjustment' => $optionValidated['price_adjustment'] ?? null,
                    'order' => $index,
                ]);
            }
        }

        // Sync materials if provided
        if ($materials && is_array($materials)) {
            $syncData = [];
            foreach ($materials as $materialData) {
                $materialValidated = validator($materialData, [
                    'material_id' => ['required', 'exists:materials,id'],
                    'quantity_required' => ['required', 'numeric', 'min:0'],
                ])->validate();

                $syncData[$materialValidated['material_id']] = [
                    'quantity_required' => $materialValidated['quantity_required'],
                ];
            }
            $product->materials()->sync($syncData);
        }

        return new ProductResource($product->fresh()->load(['category', 'variants', 'personalizationOptions', 'materials']));
    }

    /**
     * Update a product
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('products')->ignore($product->id)],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('products')->ignore($product->id)],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'width' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'shipping_class' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:50'],
            'featured' => ['boolean'],
            'status' => ['sometimes', Rule::in(['active', 'draft'])],
            'thumbnail_url' => ['nullable', 'url'],
            'original_url' => ['nullable', 'url'],
            'gallery' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'variants' => ['nullable', 'array'],
            // New Etsy features
            'inventory_type' => ['nullable', Rule::in(['in_stock', 'made_to_order', 'both'])],
            'production_time_days' => ['nullable', 'integer', 'min:0'],
            'min_quantity' => ['nullable', 'integer', 'min:1'],
            'max_quantity' => ['nullable', 'integer', 'min:1'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'track_inventory' => ['boolean'],
            'cost_of_goods' => ['nullable', 'numeric', 'min:0'],
            'materials_required' => ['nullable', 'array'],
            'personalization_options' => ['nullable', 'array'],
            'materials' => ['nullable', 'array'],
        ]);

        // Update slug if name changed
        if (isset($validated['name']) && $validated['name'] !== $product->name) {
            $slug = Str::slug($validated['name']);
            $uniqueSlug = $slug;
            $counter = 1;
            while (Product::where('slug', $uniqueSlug)->where('id', '!=', $product->id)->exists()) {
                $uniqueSlug = $slug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $uniqueSlug;
        }

        $variants = $validated['variants'] ?? null;
        $personalizationOptions = $validated['personalization_options'] ?? null;
        $materials = $validated['materials'] ?? null;
        unset($validated['variants'], $validated['personalization_options'], $validated['materials']);

        $product->update($validated);

        // Update variants if provided
        if ($variants !== null && is_array($variants)) {
            // Delete existing variants
            $product->variants()->delete();

            // Create new variants
            foreach ($variants as $variantData) {
                $variantValidated = validator($variantData, [
                    'sku' => ['nullable', 'string', 'max:255'],
                    'attributes' => ['required', 'array'],
                    'price' => ['nullable', 'numeric', 'min:0'],
                    'sale_price' => ['nullable', 'numeric', 'min:0'],
                    'quantity' => ['required', 'integer', 'min:0'],
                    'image_url' => ['nullable', 'url'],
                ])->validate();

                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $variantValidated['sku'] ?? null,
                    'attributes' => $variantValidated['attributes'],
                    'price' => $variantValidated['price'] ?? null,
                    'sale_price' => $variantValidated['sale_price'] ?? null,
                    'quantity' => $variantValidated['quantity'],
                    'image_url' => $variantValidated['image_url'] ?? null,
                ]);
            }
        }

        // Update personalization options if provided
        if ($personalizationOptions !== null && is_array($personalizationOptions)) {
            // Delete existing options
            $product->personalizationOptions()->delete();

            // Create new options
            foreach ($personalizationOptions as $index => $optionData) {
                $optionValidated = validator($optionData, [
                    'name' => ['required', 'string', 'max:255'],
                    'type' => ['required', Rule::in(['text', 'number', 'select', 'color', 'file_upload', 'checkbox'])],
                    'required' => ['boolean'],
                    'options' => ['nullable', 'array'],
                    'max_length' => ['nullable', 'integer', 'min:1'],
                    'price_adjustment' => ['nullable', 'numeric', 'min:0'],
                ])->validate();

                ProductPersonalizationOption::create([
                    'product_id' => $product->id,
                    'name' => $optionValidated['name'],
                    'type' => $optionValidated['type'],
                    'required' => $optionValidated['required'] ?? false,
                    'options' => $optionValidated['options'] ?? null,
                    'max_length' => $optionValidated['max_length'] ?? null,
                    'price_adjustment' => $optionValidated['price_adjustment'] ?? null,
                    'order' => $index,
                ]);
            }
        }

        // Sync materials if provided
        if ($materials !== null && is_array($materials)) {
            $syncData = [];
            foreach ($materials as $materialData) {
                $materialValidated = validator($materialData, [
                    'material_id' => ['required', 'exists:materials,id'],
                    'quantity_required' => ['required', 'numeric', 'min:0'],
                ])->validate();

                $syncData[$materialValidated['material_id']] = [
                    'quantity_required' => $materialValidated['quantity_required'],
                ];
            }
            $product->materials()->sync($syncData);
        }

        return new ProductResource($product->fresh()->load(['category', 'variants', 'personalizationOptions', 'materials']));
    }

    /**
     * Delete a product
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }
}
