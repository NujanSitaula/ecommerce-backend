<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Seeder;

class CountryStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countryState = app()->make('CountryState');

        // CountryState::getCountries() returns an array keyed by ISO2 => English name.
        $countries = $countryState->getCountries();

        foreach ($countries as $iso2 => $countryName) {
            $iso3 = null;
            $phoneCode = null;

            // Enrich iso3 + phone_code when available from the underlying country object.
            try {
                $countryObj = $countryState->getCountry((string) $iso2);

                if (method_exists($countryObj, 'getIsoAlpha3')) {
                    $iso3 = $countryObj->getIsoAlpha3();
                }

                if (method_exists($countryObj, 'getCallingCode')) {
                    $phoneCode = $countryObj->getCallingCode();
                    if (is_array($phoneCode)) {
                        $phoneCode = $phoneCode[0] ?? null;
                    }
                }
            } catch (\Throwable $e) {
                // Keep iso3/phone_code null when enrichment isn't available.
            }

            $country = Country::updateOrCreate(
                ['iso2' => (string) $iso2],
                [
                    'name' => (string) $countryName,
                    'iso3' => $iso3 ? (string) $iso3 : null,
                    'phone_code' => $phoneCode ? (string) $phoneCode : null,
                    'is_active' => true,
                ],
            );

            // Refresh the state list so it matches the dataset used by this seeder.
            $country->states()->delete();

            $states = $countryState->getStates((string) $iso2); // code => name
            foreach ($states as $code => $stateName) {
                State::updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'code' => $code ? (string) $code : null,
                    ],
                    [
                        'name' => (string) $stateName,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}


