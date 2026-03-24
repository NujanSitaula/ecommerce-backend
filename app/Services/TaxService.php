<?php

namespace App\Services;

use App\Models\Address;
use App\Models\TaxRate;

class TaxService
{
    /**
     * Get tax info for an address. Most specific match wins: state > country > default.
     *
     * @return array{rate: float, tax_type: string, shipping_taxable: bool}
     */
    public function getTaxForAddress(Address $address): array
    {
        $address->loadMissing(['country', 'state']);

        // Try state-level match first
        if ($address->state_id) {
            $taxRate = TaxRate::active()
                ->where('state_id', $address->state_id)
                ->first();

            if ($taxRate) {
                return [
                    'rate' => (float) $taxRate->rate,
                    'tax_type' => $taxRate->tax_type,
                    'shipping_taxable' => $taxRate->shipping_taxable,
                ];
            }
        }

        // Try country-level match
        if ($address->country_id) {
            $taxRate = TaxRate::active()
                ->where('country_id', $address->country_id)
                ->whereNull('state_id')
                ->first();

            if ($taxRate) {
                return [
                    'rate' => (float) $taxRate->rate,
                    'tax_type' => $taxRate->tax_type,
                    'shipping_taxable' => $taxRate->shipping_taxable,
                ];
            }
        }

        // Fallback to default rate
        $taxRate = TaxRate::active()
            ->where('is_default', true)
            ->first();

        if ($taxRate) {
            return [
                'rate' => (float) $taxRate->rate,
                'tax_type' => $taxRate->tax_type,
                'shipping_taxable' => $taxRate->shipping_taxable,
            ];
        }

        // No tax if no rate found
        return [
            'rate' => 0.0,
            'tax_type' => 'vat',
            'shipping_taxable' => false,
        ];
    }

    /**
     * Calculate tax amount for given totals.
     *
     * @param  array{rate: float, tax_type: string, shipping_taxable: bool}  $taxInfo
     * @return array{tax_amount: float, tax_on_products: float, tax_on_shipping: float}
     */
    public function calculateTax(
        float $subtotal,
        float $discountAmount,
        float $shippingFee,
        array $taxInfo
    ): array {
        $taxableProducts = max(0.0, $subtotal - $discountAmount);
        $rate = $taxInfo['rate'] ?? 0;
        $shippingTaxable = $taxInfo['shipping_taxable'] ?? false;

        $taxOnProducts = $taxableProducts * ($rate / 100);
        $taxOnShipping = $shippingTaxable ? $shippingFee * ($rate / 100) : 0.0;
        $taxAmount = round($taxOnProducts + $taxOnShipping, 2);

        return [
            'tax_amount' => $taxAmount,
            'tax_on_products' => round($taxOnProducts, 2),
            'tax_on_shipping' => round($taxOnShipping, 2),
        ];
    }
}
