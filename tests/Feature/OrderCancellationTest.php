<?php

use App\Enums\CancellationRequestStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\OrderCancellationRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createPaidOrderForCancellation(): array
{
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();

    $product = Product::query()->create([
        'user_id' => $vendor->id,
        'name' => 'İptal Test Ürün',
        'price' => 500,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'CANCEL-TEST-1',
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 5,
    ]);

    app(CartService::class)->addItem($customer, $variant, 1);

    $order = app(OrderService::class)->checkout($customer);
    app(OrderService::class)->chargePaymentDirectly($order, '127.0.0.1');

    return compact('vendor', 'customer', 'order', 'variant');
}

it('lets customers create a cancellation request for a paid order', function () {
    ['customer' => $customer, 'order' => $order] = createPaidOrderForCancellation();

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->postJson("/api/orders/{$order->id}/cancellation-request", [
            'message' => 'Yanlışlıkla sipariş verdim, lütfen iptal edin.',
        ])
        ->assertCreated()
        ->assertJsonPath('cancellation_request.status', CancellationRequestStatus::Pending->value);

    expect(OrderCancellationRequest::query()->count())->toBe(1);
});

it('shows pending cancellation requests to the product vendor', function () {
    ['vendor' => $vendor, 'customer' => $customer, 'order' => $order] = createPaidOrderForCancellation();

    OrderCancellationRequest::query()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'message' => 'Ürün artık gerekmiyor.',
        'status' => CancellationRequestStatus::Pending,
    ]);

    $this->withToken($vendor->createToken('test')->plainTextToken)
        ->getJson('/api/admin/cancellation-requests?status=pending')
        ->assertOk()
        ->assertJsonPath('pending_count', 1)
        ->assertJsonCount(1, 'cancellation_requests')
        ->assertJsonPath('cancellation_requests.0.message', 'Ürün artık gerekmiyor.');
});

it('approves a cancellation request, refunds payment and restores stock', function () {
    ['vendor' => $vendor, 'customer' => $customer, 'order' => $order, 'variant' => $variant] = createPaidOrderForCancellation();
    $admin = User::factory()->admin()->create();

    $request = OrderCancellationRequest::query()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'message' => 'Adres değişti, iptal istiyorum.',
        'status' => CancellationRequestStatus::Pending,
    ]);

    $stockBefore = $variant->fresh()->stock?->quantity;

    $this->withToken($admin->createToken('test')->plainTextToken)
        ->postJson("/api/admin/cancellation-requests/{$request->id}/approve")
        ->assertOk()
        ->assertJsonPath('cancellation_request.status', CancellationRequestStatus::Approved->value);

    $order->refresh();
    $request->refresh();

    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->payment_status)->toBe(PaymentStatus::Refunded)
        ->and($request->refund_reference)->not->toBeNull()
        ->and($variant->fresh()->stock?->quantity)->toBe($stockBefore + 1);
});

it('forbids vendors from approving cancellation requests', function () {
    ['vendor' => $vendor, 'customer' => $customer, 'order' => $order] = createPaidOrderForCancellation();

    $request = OrderCancellationRequest::query()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'message' => 'İptal talebi test mesajı.',
        'status' => CancellationRequestStatus::Pending,
    ]);

    $this->withToken($vendor->createToken('test')->plainTextToken)
        ->postJson("/api/admin/cancellation-requests/{$request->id}/approve")
        ->assertForbidden();
});

it('includes cancellation request on customer order detail', function () {
    ['customer' => $customer, 'order' => $order] = createPaidOrderForCancellation();

    OrderCancellationRequest::query()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'message' => 'Müşteri iptal mesajı burada.',
        'status' => CancellationRequestStatus::Pending,
    ]);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->getJson("/api/orders/{$order->id}")
        ->assertOk()
        ->assertJsonPath('order.cancellation_request.status', CancellationRequestStatus::Pending->value)
        ->assertJsonPath('order.cancellation_request.message', 'Müşteri iptal mesajı burada.');
});
