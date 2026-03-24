<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clean up any previously-seeded demo categories so we end up with exactly one demo category.
        $demoSlugs = [
            'souvenirs',
            'bags',
            'pashmina',
            'collectibles',
            'gifts',
            'home-decor',
            'stationery',
            'jewelry',
            'apparel',
            'artisan-crafts',
        ];

        Category::query()
            ->whereIn('slug', $demoSlugs)
            ->where('slug', '!=', 'bags')
            ->delete();

        Category::updateOrCreate(
            ['slug' => 'bags'],
            [
                'name' => 'Bags',
                'description' => 'Bags category',
                'is_active' => true,
            ]
        );
    }
}


