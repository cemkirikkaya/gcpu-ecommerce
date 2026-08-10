<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createPendingOrderForRetry(User $customer): Order
{
    $product = Product::query()->create([
        'name' => 'Retry Ürün',
        'price' => 750,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'RETRY-1',
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 5,
    ]);

    app(CartService::class)->addItem($customer, $variant, 1);

    return app(OrderService::class)->checkout($customer);
}

it('includes payment options when showing a payable order', function () {
    $customer = User::factory()->create();
    $order = createPendingOrderForRetry($customer);

    Sanctum::actingAs($customer);

    $this->getJson("/api/orders/{$order->id}")
        ->assertOk()
        ->assertJsonPath('order.payment_status', 'pending')
        ->assertJsonStructure([
            'payment_options' => [
                'direct_payment',
                'payment_providers',
            ],
        ]);
});

it('allows retrying payment for failed orders', function () {
    Config::set('stripe.fake', true);
    Config::set('payments.providers.stripe.enabled', true);

    $customer = User::factory()->create();
    $order = createPendingOrderForRetry($customer);
    $order->update(['payment_status' => 'failed']);

    Sanctum::actingAs($customer);

    $this->getJson("/api/orders/{$order->id}")
        ->assertOk()
        ->assertJsonStructure(['payment_options']);

    $this->postJson("/api/orders/{$order->id}/payments/stripe/init")
        ->assertSuccessful()
        ->assertJsonStructure(['payment_page_url']);
});

it('does not include payment options for paid orders', function () {
    $customer = User::factory()->create();
    $order = createPendingOrderForRetry($customer);
    app(OrderService::class)->completePayment($order);

    Sanctum::actingAs($customer);

    $this->getJson("/api/orders/{$order->id}")
        ->assertOk()
        ->assertJsonMissingPath('payment_options');
});

it('forbids payment retry for another customers order', function () {
    $customer = User::factory()->create();
    $other = User::factory()->create();
    $order = createPendingOrderForRetry($customer);

    Sanctum::actingAs($other);

    $this->postJson("/api/orders/{$order->id}/payments/stripe/init")
        ->assertForbidden();
});

it('returns installment options for payable orders when direct payment is enabled', function () {
    Config::set('iyzico.direct', true);
    Config::set('iyzico.fake', true);

    $customer = User::factory()->create();
    $order = createPendingOrderForRetry($customer);

    Sanctum::actingAs($customer);

    $this->getJson("/api/orders/{$order->id}/installments")
        ->assertOk()
        ->assertJsonStructure(['installments', 'direct_payment']);
});
