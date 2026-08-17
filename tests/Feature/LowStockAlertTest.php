<?php

use App\Mail\LowStockMail;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\CartService;
use App\Services\LowStockService;
use App\Services\OrderService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function createVendorVariantWithStock(User $vendor, int $quantity, string $sku = 'LOW-STOCK-1'): ProductVariant
{
    $product = Product::query()->create([
        'user_id' => $vendor->id,
        'name' => 'Düşük Stok Ürün',
        'price' => 250,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => $sku,
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => $quantity,
    ]);

    return $variant->fresh(['stock', 'product.vendor']);
}

it('includes low stock alerts in admin summary', function () {
    Config::set('shop.low_stock_threshold', 5);

    $vendor = User::factory()->vendor()->create();
    createVendorVariantWithStock($vendor, 3);

    $this->withToken($vendor->createToken('test')->plainTextToken)
        ->getJson('/api/admin/summary')
        ->assertOk()
        ->assertJsonPath('summary.low_stock_threshold', 5)
        ->assertJsonPath('summary.low_stock_variants', 1)
        ->assertJsonPath('summary.low_stock_alerts.0.sku', 'LOW-STOCK-1')
        ->assertJsonPath('summary.low_stock_alerts.0.quantity', 3);
});

it('queues a low stock email when inventory crosses the threshold', function () {
    Mail::fake();
    Config::set('shop.low_stock_threshold', 5);

    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $variant = createVendorVariantWithStock($vendor, 6);

    app(CartService::class)->addItem($customer, $variant, 1);
    $order = app(OrderService::class)->checkout($customer);
    app(OrderService::class)->chargePaymentDirectly($order, '127.0.0.1');

    Mail::assertQueued(LowStockMail::class, function (LowStockMail $mail) use ($vendor, $variant) {
        return $mail->hasTo($vendor->email)
            && $mail->variant->is($variant->fresh())
            && $mail->quantity === 5
            && $mail->threshold === 5;
    });
});

it('does not queue duplicate low stock emails for the same variant', function () {
    Mail::fake();
    Config::set('shop.low_stock_threshold', 5);

    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $variant = createVendorVariantWithStock($vendor, 6);

    app(CartService::class)->addItem($customer, $variant, 1);
    $order = app(OrderService::class)->checkout($customer);
    app(OrderService::class)->chargePaymentDirectly($order, '127.0.0.1');

    app(StockService::class)->decrementStock($variant->fresh(['stock', 'product.vendor']), 1);

    Mail::assertQueued(LowStockMail::class, 1);
});

it('queues a low stock email after manual stock update crosses the threshold', function () {
    Mail::fake();
    Config::set('shop.low_stock_threshold', 5);

    $vendor = User::factory()->vendor()->create();
    $variant = createVendorVariantWithStock($vendor, 8);

    app(LowStockService::class)->evaluateVariant($variant, 8);
    $variant->stock?->update(['quantity' => 4]);
    app(LowStockService::class)->evaluateVariant($variant->fresh(['stock', 'product.vendor']), 8);

    Mail::assertQueued(LowStockMail::class, 1);
});

it('clears low stock notification state when inventory is restored above threshold', function () {
    Mail::fake();
    Config::set('shop.low_stock_threshold', 5);

    $vendor = User::factory()->vendor()->create();
    $variant = createVendorVariantWithStock($vendor, 6);

    app(LowStockService::class)->evaluateVariant($variant, 6);
    $variant->stock?->update(['quantity' => 4]);
    app(LowStockService::class)->evaluateVariant($variant->fresh(['stock', 'product.vendor']), 6);

    Mail::assertQueued(LowStockMail::class, 1);

    $variant->stock?->update(['quantity' => 10]);
    app(LowStockService::class)->evaluateVariant($variant->fresh(['stock', 'product.vendor']), 4);

    $variant->stock?->update(['quantity' => 3]);
    app(LowStockService::class)->evaluateVariant($variant->fresh(['stock', 'product.vendor']), 10);

    Mail::assertQueued(LowStockMail::class, 2);
});

it('includes low stock email content details', function () {
    Config::set('shop.low_stock_threshold', 5);

    $vendor = User::factory()->vendor()->create(['name' => 'Vendor Satıcı']);
    $variant = createVendorVariantWithStock($vendor, 2, 'SKU-LOW-2');

    $mailable = new LowStockMail($variant, 2, 5);

    $mailable->assertSeeInHtml('Düşük Stok Ürün');
    $mailable->assertSeeInHtml('SKU-LOW-2');
    $mailable->assertSeeInHtml('Vendor Satıcı');
});
