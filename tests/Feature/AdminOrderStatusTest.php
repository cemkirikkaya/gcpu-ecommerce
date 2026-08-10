<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createPaidOrder(): Order
{
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();

    $product = Product::query()->create([
        'user_id' => $vendor->id,
        'name' => 'Test Ürün',
        'price' => 250,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'TEST-ORDER-STATUS-1',
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 10,
    ]);

    app(CartService::class)->addItem($customer, $variant, 1);

    $order = app(OrderService::class)->checkout($customer);
    app(OrderService::class)->completePayment($order);

    return $order->fresh();
}

it('lets platform admin update order status through the api', function () {
    $order = createPaidOrder();

    $this->withToken(User::factory()->admin()->create()->createToken('test')->plainTextToken)
        ->patchJson("/api/admin/orders/{$order->id}", [
            'status' => OrderStatus::Shipped->value,
        ])
        ->assertOk()
        ->assertJsonPath('order.status', OrderStatus::Shipped->value)
        ->assertJsonPath('message', 'Sipariş durumu güncellendi.');

    expect($order->fresh()->status)->toBe(OrderStatus::Shipped);
});

it('rejects invalid order status transitions', function () {
    $order = createPaidOrder();

    $this->withToken(User::factory()->admin()->create()->createToken('test')->plainTextToken)
        ->patchJson("/api/admin/orders/{$order->id}", [
            'status' => OrderStatus::Delivered->value,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Bu sipariş durumu güncellenemez.');
});

it('forbids vendors from updating order status', function () {
    $order = createPaidOrder();
    $vendor = User::factory()->vendor()->create();

    $this->withToken($vendor->createToken('test')->plainTextToken)
        ->patchJson("/api/admin/orders/{$order->id}", [
            'status' => OrderStatus::Shipped->value,
        ])
        ->assertForbidden();
});

it('updates order status to delivered after shipped', function () {
    $order = createPaidOrder();
    $order->update(['status' => OrderStatus::Shipped]);

    $this->withToken(User::factory()->admin()->create()->createToken('test')->plainTextToken)
        ->patchJson("/api/admin/orders/{$order->id}", [
            'status' => OrderStatus::Delivered->value,
        ])
        ->assertOk()
        ->assertJsonPath('order.status', OrderStatus::Delivered->value);
});
