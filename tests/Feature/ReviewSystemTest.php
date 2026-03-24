<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\ContactNumber;
use App\Models\Country;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ReviewSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure Passport has the encryption keys + personal access client in the
        // in-memory DB (non-interactive to keep tests deterministic).
        $this->artisan('passport:keys', ['--force' => true])->assertExitCode(0);
        $this->artisan('passport:client', [
            '--personal' => true,
            '--name' => 'Test Personal Access Client',
            '--provider' => 'users',
        ])->assertExitCode(0);
    }

    private function createPurchaseContext(User $user, Product $product): Order
    {
        $country = Country::create([
            'name' => 'Test Country',
            'iso2' => 'TC',
            'iso3' => 'TST',
            'phone_code' => '1',
            'is_active' => true,
        ]);

        $address = Address::create([
            'user_id' => $user->id,
            'title' => 'Home',
            'name' => $user->name,
            'phone' => '5551234',
            'address_line1' => '123 Main St',
            'address_line2' => null,
            'city' => 'Test City',
            'postal_code' => '12345',
            'country_id' => $country->id,
            'state_id' => null,
            'is_default' => true,
        ]);

        $contactNumber = ContactNumber::create([
            'user_id' => $user->id,
            'title' => 'Mobile',
            'phone' => '5551234',
            'is_default' => true,
        ]);

        $paymentMethod = PaymentMethod::create([
            'user_id' => $user->id,
            'provider' => 'stripe',
            'stripe_customer_id' => null,
            'stripe_payment_method_id' => 'pm_test_123',
            'brand' => 'visa',
            'last4' => '4242',
            'exp_month' => 1,
            'exp_year' => 2030,
            'cardholder_name' => $user->name,
            'is_default' => true,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'contact_number_id' => $contactNumber->id,
            'payment_method_id' => $paymentMethod->id,
            'stripe_payment_intent_id' => null,
            'delivery_date' => now()->addDay()->toDateString(),
            'gift_wrapped' => false,
            'delivery_instructions' => null,
            'leave_at_door' => false,
            'subtotal' => 10.00,
            'shipping_fee' => 0.00,
            'discount_amount' => 0.00,
            'shipping_discount' => 0.00,
            'tax_amount' => 0.00,
            'tax_rate' => 0.00,
            'tax_type' => 'vat',
            'total' => 10.00,
            'status' => 'confirmed',
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'quantity' => 1,
            'price' => $product->price,
            'subtotal' => $product->price,
        ]);

        return $order;
    }

    private function createProductWithCategory(): Product
    {
        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => null,
            'is_active' => true,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'Line 1 description.' . PHP_EOL . 'Line 2 description.',
            'price' => 10.00,
            'sale_price' => null,
            'currency' => 'USD',
            'quantity' => 10,
            'unit' => 'pcs',
            'type' => null,
            'featured' => false,
            'status' => 'active',
            'thumbnail_url' => null,
            'original_url' => null,
            'gallery' => null,
            'tags' => null,
        ]);
    }

    public function test_reviews_are_pending_until_approved_and_then_visible()
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Customer',
            'email' => 'customer@example.com',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        $product = $this->createProductWithCategory();
        $this->createPurchaseContext($customer, $product);

        Passport::actingAs($customer);

        $submit = $this->postJson("/api/products/{$product->slug}/reviews", [
            'rating' => 5,
            'title' => 'Great product',
            'message' => 'Loved it.',
            'name' => $customer->name,
            'email' => $customer->email,
        ]);

        $submit->assertStatus(201);

        $review = Review::query()
            ->where('product_id', $product->id)
            ->where('user_id', $customer->id)
            ->first();

        $this->assertNotNull($review);
        $this->assertSame('pending', $review->status);

        // Not visible publicly before approval
        $before = $this->getJson("/api/products/{$product->slug}/reviews");
        $before->assertStatus(200);
        $this->assertCount(0, (array) ($before->json('data') ?? []));

        Passport::actingAs($admin);

        $approve = $this->postJson("/api/admin/reviews/{$review->id}/approve");
        $approve->assertStatus(200);

        $review->refresh();
        $this->assertSame('approved', $review->status);

        // Visible publicly after approval
        $after = $this->getJson("/api/products/{$product->slug}/reviews");
        $after->assertStatus(200);
        $this->assertNotEmpty($after->json('data'));
        $this->assertSame('approved', Review::query()->find($review->id)->status);
    }

    public function test_admin_can_hide_and_delete_reviews()
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Customer',
            'email' => 'customer2@example.com',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin',
            'email' => 'admin2@example.com',
        ]);

        $product = $this->createProductWithCategory();
        $this->createPurchaseContext($customer, $product);

        Passport::actingAs($customer);

        $submit = $this->postJson("/api/products/{$product->slug}/reviews", [
            'rating' => 4,
            'title' => 'Nice',
            'message' => 'Pretty good.',
            'name' => $customer->name,
            'email' => $customer->email,
        ]);
        $submit->assertStatus(201);

        $review = Review::query()
            ->where('product_id', $product->id)
            ->where('user_id', $customer->id)
            ->first();

        Passport::actingAs($admin);

        // First approve so it would be visible, then hide and ensure it disappears.
        $approve = $this->postJson("/api/admin/reviews/{$review->id}/approve");
        $approve->assertStatus(200);

        $visible = $this->getJson("/api/products/{$product->slug}/reviews");
        $visible->assertStatus(200);
        $this->assertNotEmpty($visible->json('data'));

        $hide = $this->postJson("/api/admin/reviews/{$review->id}/hide");
        $hide->assertStatus(200);

        $afterHide = $this->getJson("/api/products/{$product->slug}/reviews");
        $afterHide->assertStatus(200);
        $this->assertCount(0, (array) ($afterHide->json('data') ?? []));

        $delete = $this->deleteJson("/api/admin/reviews/{$review->id}");
        $delete->assertStatus(200);

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }
}

