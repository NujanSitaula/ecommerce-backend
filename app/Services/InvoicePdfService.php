<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Order;
use App\Models\State;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Cache;

class InvoicePdfService
{
    public function generate(Order $order): string
    {
        $order->load(['items', 'address.country', 'address.state', 'user']);

        $settings = Cache::get('store_settings', []);
        $companyName = $settings['invoiceCompanyName'] ?? $settings['name'] ?? config('app.name');
        $companyAddress = $settings['invoiceAddress'] ?? $settings['shippingFrom'] ?? '';
        $companyEmail = $settings['invoiceEmail'] ?? $settings['supportEmail'] ?? '';
        $companyPhone = $settings['invoicePhone'] ?? '';
        $logoUrl = $settings['invoiceLogo'] ?? null;
        $currency = $settings['currency'] ?? 'USD';

        $billTo = $this->getBillTo($order);

        $data = [
            'order' => $order,
            'companyName' => $companyName,
            'companyAddress' => $companyAddress,
            'companyEmail' => $companyEmail,
            'companyPhone' => $companyPhone,
            'logoUrl' => $logoUrl,
            'billTo' => $billTo,
            'currency' => $currency,
        ];

        $pdf = Pdf::loadView('invoices.order', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    private function getBillTo(Order $order): array
    {
        if ($order->user_id && $order->address) {
            $addr = $order->address;
            $country = $addr->country?->name ?? '';
            $state = $addr->state?->name ?? '';
            $lines = array_filter([
                $addr->name,
                $addr->address_line1,
                $addr->address_line2,
                trim($addr->city . ' ' . $addr->postal_code),
                trim($state . ($state && $country ? ', ' : '') . $country),
            ]);
            return [
                'name' => $addr->name,
                'lines' => $lines,
                'email' => $order->user?->email ?? null,
            ];
        }

        $guestAddr = $order->guest_address;
        $name = $order->guest_name ?? ($guestAddr['name'] ?? 'Guest');
        $email = $order->guest_email;

        if (is_array($guestAddr)) {
            $countryId = $guestAddr['country_id'] ?? null;
            $stateId = $guestAddr['state_id'] ?? null;
            $country = $countryId ? (Country::find($countryId)?->name ?? '') : '';
            $state = $stateId ? (State::find($stateId)?->name ?? '') : '';
            $city = $guestAddr['city'] ?? '';
            $postal = $guestAddr['postal_code'] ?? '';
            $lines = array_filter([
                $guestAddr['name'] ?? $name,
                $guestAddr['address_line1'] ?? null,
                $guestAddr['address_line2'] ?? null,
                trim($city . ' ' . $postal),
                trim($state . ($state && $country ? ', ' : '') . $country),
            ]);
        } else {
            $lines = array_filter([$name, $email]);
        }

        return [
            'name' => $name,
            'lines' => $lines,
            'email' => $email,
        ];
    }
}
