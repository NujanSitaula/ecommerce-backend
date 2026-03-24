<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Country;
use App\Services\ShippingService;
use App\Services\TaxService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShippingController extends Controller
{
    public function __construct(
        protected ShippingService $shippingService,
        protected TaxService $taxService,
    ) {
    }

    public function quote(Request $request)
    {
        // Authenticated users: quote by address_id
        if ($request->user()) {
            $data = $request->validate([
                'address_id' => 'required|integer',
            ]);

            try {
                $quote = $this->shippingService->getShippingQuote(
                    (int) $data['address_id'],
                    $request->user(),
                );

                return response()->json($quote);
            } catch (ValidationException $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }
        }

        // Guests: quote by country_id only (flat per-country rate)
        $data = $request->validate([
            'country_id' => 'required|integer',
            'state_id' => 'nullable|integer|exists:states,id',
        ]);

        /** @var Country|null $country */
        $country = Country::find($data['country_id']);

        if (!$country) {
            return response()->json([
                'message' => 'Invalid country.',
                'errors' => [
                    'country_id' => ['Country not found.'],
                ],
            ], 422);
        }

        // Create an in-memory Address model with the selected country and optional state
        $address = new Address();
        $address->country_id = $country->id;
        $address->state_id = $data['state_id'] ?? null;
        $address->setRelation('country', $country);

        if (!empty($data['state_id'])) {
            $address->setRelation('state', \App\Models\State::find($data['state_id']));
        }

        $fee = $this->shippingService->calculateShippingFee($address);
        $taxInfo = $this->taxService->getTaxForAddress($address);

        return response()->json([
            'shipping_fee' => $fee,
            'tax_rate' => $taxInfo['rate'],
            'tax_type' => $taxInfo['tax_type'],
            'shipping_taxable' => $taxInfo['shipping_taxable'],
        ]);
    }
}


