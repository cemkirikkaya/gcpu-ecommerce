<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createPaidOrderForVariant(User $customer, ProductVariant $variant, int $quantity = 1): Order
{
    app(CartService::class)->addItem($customer, $variant, $quantity);

    $order = app(OrderService::class)->checkout($customer);
    app(OrderService::class)->completePayment($order);

    return $order->fresh();
}

function createVendorProduct(User $vendor, string $name, float $price, string $sku): ProductVariant
{
    $product = Product::query()->create([
        'user_id' => $vendor->id,
        'name' => $name,
        'price' => $price,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => $sku,
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 20,
    ]);

    return $variant;
}

it('returns vendor-scoped admin summary metrics', function () {
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $variant = createVendorProduct($vendor, 'Vendor Kulaklık', 500, 'VND-HP-1');

    createPaidOrderForVariant($customer, $variant, 2);

    $this->withToken($vendor->createToken('test')->plainTextToken)
        ->getJson('/api/admin/summary')
        ->assertOk()
        ->assertJsonPath('summary.products_count', 1)
        ->assertJsonPath('summary.orders_count', 1)
        ->assertJsonPath('summary.items_sold', 2)
        ->assertJsonPath('summary.revenue', 1000)
        ->assertJsonStructure([
            'summary' => [
                'charts' => [
                    'revenue_trend',
                    'orders_by_status',
                    'top_products',
                ],
            ],
        ])
        ->assertJsonPath('summary.charts.top_products.0.name', 'Vendor Kulaklık')
        ->assertJsonPath('summary.charts.top_products.0.quantity', 2);
});

it('lists only orders containing vendor products', function () {
    $vendorA = User::factory()->vendor()->create();
    $vendorB = User::factory()->vendor()->create();
    $customer = User::factory()->create();

    $variantA = createVendorProduct($vendorA, 'A Ürün', 100, 'VA-1');
    $variantB = createVendorProduct($vendorB, 'B Ürün', 200, 'VB-1');

    createPaidOrderForVariant($customer, $variantA, 1);
    createPaidOrderForVariant($customer, $variantB, 1);

    $this->withToken($vendorA->createToken('test')->plainTextToken)
        ->getJson('/api/admin/orders')
        ->assertOk()
        ->assertJsonCount(1, 'orders')
        ->assertJsonPath('orders.0.total_price', 100)
        ->assertJsonPath('orders.0.items_count', 1);
});

it('shows only vendor items on admin order detail', function () {
    $vendorA = User::factory()->vendor()->create();
    $vendorB = User::factory()->vendor()->create();
    $customer = User::factory()->create();

    $variantA = createVendorProduct($vendorA, 'A Ürün', 100, 'VA-DETAIL-1');
    $variantB = createVendorProduct($vendorB, 'B Ürün', 200, 'VB-DETAIL-1');

    app(CartService::class)->addItem($customer, $variantA, 1);
    app(CartService::class)->addItem($customer, $variantB, 2);

    $order = app(OrderService::class)->checkout($customer);

    expect($order->items)->toHaveCount(2)
        ->and((float) $order->total_price)->toBe(500.0);

    app(OrderService::class)->completePayment($order);

    expect((float) $order->fresh()->total_price)->toBe(500.0);

    $response = $this->withToken($vendorA->createToken('test')->plainTextToken)
        ->getJson("/api/admin/orders/{$order->id}");

    $response
        ->assertOk()
        ->assertJsonPath('order.vendor_subtotal', 100)
        ->assertJsonPath('order.order_total', 500)
        ->assertJsonCount(1, 'order.items')
        ->assertJsonPath('order.items.0.product_name', 'A Ürün')
        ->assertJsonMissingPath('order.address');
});

it('shows full order totals for platform admin order detail', function () {
    $vendorA = User::factory()->vendor()->create();
    $vendorB = User::factory()->vendor()->create();
    $customer = User::factory()->create();

    $variantA = createVendorProduct($vendorA, 'A Ürün', 100, 'VA-ADMIN-1');
    $variantB = createVendorProduct($vendorB, 'B Ürün', 200, 'VB-ADMIN-1');

    app(CartService::class)->addItem($customer, $variantA, 1);
    app(CartService::class)->addItem($customer, $variantB, 2);

    $order = app(OrderService::class)->checkout($customer);
    app(OrderService::class)->completePayment($order);

    $this->withToken(User::factory()->admin()->create()->createToken('test')->plainTextToken)
        ->getJson("/api/admin/orders/{$order->id}")
        ->assertOk()
        ->assertJsonPath('order.total_price', 500)
        ->assertJsonCount(2, 'order.items')
        ->assertJsonStructure(['order' => ['address']]);
});

it('forbids vendors from viewing orders without their products', function () {
    $vendor = User::factory()->vendor()->create();
    $otherVendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();

    $variant = createVendorProduct($otherVendor, 'Başka Ürün', 150, 'OTHER-1');
    $order = createPaidOrderForVariant($customer, $variant, 1);

    $this->withToken($vendor->createToken('test')->plainTextToken)
        ->getJson("/api/admin/orders/{$order->id}")
        ->assertForbidden();
});

it('lets platform admin list all orders', function () {
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $variant = createVendorProduct($vendor, 'Platform Test', 300, 'ADM-1');

    createPaidOrderForVariant($customer, $variant, 1);

    $this->withToken(User::factory()->admin()->create()->createToken('test')->plainTextToken)
        ->getJson('/api/admin/orders')
        ->assertOk()
        ->assertJsonCount(1, 'orders')
        ->assertJsonPath('orders.0.total_price', 300);
});
