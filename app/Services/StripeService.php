<?php

namespace App\Services;

use App\Models\User;
use Stripe\StripeClient;

class StripeService
{
    protected StripeClient $client;

    public function __construct()
    {
        $this->client = new StripeClient(config('services.stripe.secret'));
    }

    public function ensureCustomer(User $user): string
    {
        if ($user->stripe_customer_id ?? false) {
            return $user->stripe_customer_id;
        }

        $customer = $this->client->customers->create([
            'email' => $user->email,
            'name' => $user->name,
        ]);

        $user->stripe_customer_id = $customer->id;
        $user->save();

        return $customer->id;
    }

    public function createSetupIntent(User $user): string
    {
        $customerId = $this->ensureCustomer($user);

        $setupIntent = $this->client->setupIntents->create([
            'customer' => $customerId,
            'payment_method_types' => ['card'],
        ]);

        return $setupIntent->client_secret;
    }
}


