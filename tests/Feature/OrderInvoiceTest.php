<?php

use App\Enums\PaymentStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createPaidOrderForInvoice(): array
{
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create([
        'name' => 'Kurumsal Müşteri',
        'email' => 'fatura@example.com',
    ]);

    $product = Product::query()->create([
        'user_id' => $vendor->id,
        'name' => 'Fatura Test Ürün',
        'price' => 750,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'INVOICE-TEST-1',
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 5,
    ]);

    app(CartService::class)->addItem($customer, $variant, 1);

    $order = app(OrderService::class)->checkout($customer);
    app(OrderService::class)->chargePaymentDirectly($order, '127.0.0.1');

    return compact('vendor', 'customer', 'order', 'product');
}

function createPendingOrderForInvoice(): array
{
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();

    $product = Product::query()->create([
        'user_id' => $vendor->id,
        'name' => 'Bekleyen Sipariş Ürün',
        'price' => 500,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'INVOICE-PENDING-1',
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 5,
    ]);

    app(CartService::class)->addItem($customer, $variant, 1);

    $order = app(OrderService::class)->checkout($customer);

    return compact('customer', 'order');
}

it('lets customers download a pdf invoice for a paid order', function () {
    ['customer' => $customer, 'order' => $order] = createPaidOrderForInvoice();

    $response = $this->withToken($customer->createToken('test')->plainTextToken)
        ->get("/api/orders/{$order->id}/invoice");

    $response
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload("fatura-siparis-{$order->id}.pdf");

    expect(str_starts_with((string) $response->getContent(), '%PDF'))->toBeTrue();
});

it('exposes invoice availability on order detail', function () {
    ['customer' => $customer, 'order' => $order] = createPaidOrderForInvoice();

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->getJson("/api/orders/{$order->id}")
        ->assertOk()
        ->assertJsonPath('order.can_download_invoice', true);
});

it('forbids invoice download for unpaid orders', function () {
    ['customer' => $customer, 'order' => $order] = createPendingOrderForInvoice();

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Pending);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->get("/api/orders/{$order->id}/invoice")
        ->assertForbidden();
});

it('forbids invoice download for another customers order', function () {
    ['order' => $order] = createPaidOrderForInvoice();
    $other = User::factory()->create();

    $this->withToken($other->createToken('test')->plainTextToken)
        ->get("/api/orders/{$order->id}/invoice")
        ->assertForbidden();
});

it('requires authentication to download an invoice', function () {
    ['order' => $order] = createPaidOrderForInvoice();

    $this->get("/api/orders/{$order->id}/invoice")
        ->assertUnauthorized();
});
