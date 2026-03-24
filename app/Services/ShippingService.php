<?php

namespace App\Services;

use App\Models\Address;
use App\Models\ShippingRule;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ShippingService
{
    public function __construct(
        protected TaxService $taxService
    ) {
    }

    public function calculateShippingFee(Address $address): float
    {
        $country = $address->country;
        $countryCode = $country?->iso2;

        if (!$countryCode) {
            return 0.0;
        }

        /** @var ShippingRule|null $rule */
        $rule = ShippingRule::where('country_code', strtoupper($countryCode))
            ->where('is_active', true)
            ->first();

        return $rule ? (float) $rule->amount : 0.0;
    }

    /**
     * @return array{shipping_fee: float, tax_rate: float, tax_type: string, shipping_taxable: bool}
     */
    public function getShippingQuote(int $addressId, User $user): array
    {
        /** @var Address|null $address */
        $address = Address::with(['country', 'state'])
            ->where('id', $addressId)
            ->where('user_id', $user->id)
            ->first();

        if (!$address) {
            throw ValidationException::withMessages([
                'address_id' => ['Address not found.'],
            ]);
        }

        $fee = $this->calculateShippingFee($address);
        $taxInfo = $this->taxService->getTaxForAddress($address);

        return [
            'shipping_fee' => $fee,
            'tax_rate' => $taxInfo['rate'],
            'tax_type' => $taxInfo['tax_type'],
            'shipping_taxable' => $taxInfo['shipping_taxable'],
        ];
    }
}


