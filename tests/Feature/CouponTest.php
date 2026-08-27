<?php

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use App\Support\OrderPaymentLineAmounts;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function couponCustomer(): User
{
    return User::factory()->create();
}

function couponVariant(float $price = 1000): ProductVariant
{
    $product = Product::query()->create([
        'name' => 'Kupon Test Ürün',
        'price' => $price,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'COUPON-TEST-'.fake()->unique()->numerify('###'),
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 20,
    ]);

    return $variant;
}

function adminTokenForCoupons(): string
{
    return User::factory()->admin()->create()->createToken('test')->plainTextToken;
}

it('applies a percent coupon to the cart', function (): void {
    $customer = couponCustomer();
    $variant = couponVariant(1000);

    Coupon::factory()->create([
        'code' => 'YUZDE10',
        'type' => CouponType::Percent,
        'value' => 10,
    ]);

    app(CartService::class)->addItem($customer, $variant, 1);

    $response = $this->withToken($customer->createToken('test')->plainTextToken)
        ->postJson('/api/cart/coupon', ['code' => 'yuzde10']);

    $response
        ->assertOk()
        ->assertJsonPath('cart.subtotal', 1000)
        ->assertJsonPath('cart.discount_amount', 100)
        ->assertJsonPath('cart.total', 900)
        ->assertJsonPath('cart.coupon.code', 'YUZDE10');
});

it('creates a discounted order and records coupon usage after payment', function (): void {
    $customer = couponCustomer();
    $variant = couponVariant(500);

    $coupon = Coupon::factory()->fixed(50)->create([
        'code' => 'FIXED50',
    ]);

    app(CartService::class)->addItem($customer, $variant, 2);
    app(CartService::class)->applyCoupon($customer, 'FIXED50');

    $order = app(OrderService::class)->checkout($customer);

    expect($order->subtotal)->toBe('1000.00')
        ->and($order->discount_amount)->toBe('50.00')
        ->and($order->total_price)->toBe('950.00')
        ->and($order->coupon_code)->toBe('FIXED50');

    app(OrderService::class)->completePayment($order);

    expect($coupon->fresh()->used_count)->toBe(1);
});

it('rejects expired coupons', function (): void {
    $customer = couponCustomer();
    $variant = couponVariant();

    Coupon::factory()->create([
        'code' => 'EXPIRED',
        'expires_at' => now()->subDay(),
    ]);

    app(CartService::class)->addItem($customer, $variant, 1);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->postJson('/api/cart/coupon', ['code' => 'EXPIRED'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

it('lets admins manage coupons', function (): void {
    $token = adminTokenForCoupons();

    $this->withToken($token)
        ->postJson('/api/admin/coupons', [
            'code' => 'WELCOME20',
            'type' => 'percent',
            'value' => 20,
            'min_order_amount' => 250,
            'usage_limit' => 100,
            'is_active' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('coupon.code', 'WELCOME20');

    $this->withToken($token)
        ->getJson('/api/admin/coupons')
        ->assertOk()
        ->assertJsonCount(1, 'coupons');
});

it('allocates discounted payment line amounts for orders', function (): void {
    $customer = couponCustomer();
    $variant = couponVariant(100);

    Coupon::factory()->create([
        'code' => 'SPLIT10',
        'type' => CouponType::Percent,
        'value' => 10,
    ]);

    app(CartService::class)->addItem($customer, $variant, 2);
    app(CartService::class)->applyCoupon($customer, 'SPLIT10');

    $order = app(OrderService::class)->checkout($customer);
    $lineAmounts = OrderPaymentLineAmounts::forOrder($order);

    expect(array_sum($lineAmounts))->toBe(180.0)
        ->and($order->total_price)->toBe('180.00');
});
