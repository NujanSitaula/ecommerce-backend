<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bags = Category::firstOrCreate(
            ['slug' => 'bags'],
            ['name' => 'Bags', 'description' => 'Handcrafted bags']
        );

        // Clean up any previously-seeded demo products so only one demo product remains.
        Product::query()
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('slug', 'like', 'bag-item-%')
                        ->where('slug', '!=', 'bag-item-1');
                })
                    ->orWhere('slug', 'like', 'pashmina-item-%')
                    ->orWhere('slug', 'like', 'souvenir-item-%');
            })
            ->delete();

        $slug = 'bag-item-1';
        $name = 'Bag Item 1';

        $price = 99.00;
        $salePrice = null;
        $quantity = 50;

        $thumb = "https://picsum.photos/seed/{$slug}/600/600";
        $original = "https://picsum.photos/seed/{$slug}-lg/1200/1200";

        Product::updateOrCreate(
            ['slug' => $slug],
            [
                'category_id' => $bags->id,
                'name' => $name,
                'description' => 'Single demo bag product for local development.',
                'price' => $price,
                'sale_price' => $salePrice,
                'currency' => 'USD',
                'quantity' => $quantity,
                'unit' => '1 piece',
                'type' => 'bag',
                'featured' => true,
                'status' => 'active',
                'thumbnail_url' => $thumb,
                'original_url' => $original,
                'gallery' => [
                    [
                        'id' => 1,
                        'thumbnail' => $thumb,
                        'original' => $original,
                    ],
                    [
                        'id' => 2,
                        'thumbnail' => "https://picsum.photos/seed/{$slug}-alt/600/600",
                        'original' => "https://picsum.photos/seed/{$slug}-alt/1200/1200",
                    ],
                ],
                'tags' => [
                    ['id' => 1, 'name' => 'Bags', 'slug' => 'bags'],
                    ['id' => 2, 'name' => 'Handmade', 'slug' => 'handmade'],
                ],
            ]
        );
    }
}


