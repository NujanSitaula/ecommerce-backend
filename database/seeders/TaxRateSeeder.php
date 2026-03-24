<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\TaxRate;
use Illuminate\Database\Seeder;

class TaxRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // UK VAT (20%)
        $uk = Country::where('iso2', 'GB')->first();
        if ($uk) {
            TaxRate::updateOrCreate(
                [
                    'country_id' => $uk->id,
                    'state_id' => null,
                ],
                [
                    'name' => 'UK VAT',
                    'tax_type' => 'vat',
                    'rate' => 20,
                    'shipping_taxable' => true,
                    'is_default' => false,
                    'is_active' => true,
                ]
            );
        }

        // Default rate for unspecified regions (0% - no tax)
        TaxRate::updateOrCreate(
            [
                'is_default' => true,
            ],
            [
                'country_id' => null,
                'state_id' => null,
                'name' => 'Default (No Tax)',
                'tax_type' => 'vat',
                'rate' => 0,
                'shipping_taxable' => false,
                'is_default' => true,
                'is_active' => true,
            ]
        );
    }
}
