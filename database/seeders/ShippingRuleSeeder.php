<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShippingRule;

class ShippingRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ShippingRule::updateOrCreate(
            ['country_code' => 'US'],
            [
                'amount' => 50,
                'is_active' => true,
            ],
        );

        ShippingRule::updateOrCreate(
            ['country_code' => 'GB'],
            [
                'amount' => 100,
                'is_active' => true,
            ],
        );
    }
}
