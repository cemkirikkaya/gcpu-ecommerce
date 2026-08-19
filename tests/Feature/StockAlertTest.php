<?php

use App\Mail\BackInStockMail;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\StockAlert;
use App\Models\User;
use App\Services\BackInStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function createOutOfStockVariant(User $vendor, string $sku = 'STOCK-ALERT-1'): ProductVariant
{
    $product = Product::query()->create([
        'user_id' => $vendor->id,
        'name' => 'Stok Bildirim Ürün',
        'price' => 900,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => $sku,
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 0,
    ]);

    return $variant->fresh(['stock', 'product']);
}

it('lets customers subscribe to back in stock alerts for out of stock variants', function () {
    $customer = User::factory()->create();
    $variant = createOutOfStockVariant(User::factory()->vendor()->create());

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->postJson("/api/stock-alerts/variants/{$variant->id}")
        ->assertCreated()
        ->assertJsonPath('message', 'Stoğa dönünce e-posta ile bilgilendirileceksiniz.');

    expect(StockAlert::query()->where([
        'user_id' => $customer->id,
        'product_variant_id' => $variant->id,
    ])->exists())->toBeTrue();
});

it('rejects stock alert subscription when variant is in stock', function () {
    $customer = User::factory()->create();
    $vendor = User::factory()->vendor()->create();
    $variant = createOutOfStockVariant($vendor);
    $variant->stock?->update(['quantity' => 5]);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->postJson("/api/stock-alerts/variants/{$variant->id}")
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Bu varyant şu an stokta.');
});

it('returns subscribed variant ids for the authenticated customer', function () {
    $customer = User::factory()->create();
    $variant = createOutOfStockVariant(User::factory()->vendor()->create());

    StockAlert::query()->create([
        'user_id' => $customer->id,
        'product_variant_id' => $variant->id,
    ]);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->getJson('/api/stock-alerts/variant-ids')
        ->assertOk()
        ->assertJsonPath('variant_ids.0', $variant->id);
});

it('lets customers unsubscribe from stock alerts', function () {
    $customer = User::factory()->create();
    $variant = createOutOfStockVariant(User::factory()->vendor()->create());

    StockAlert::query()->create([
        'user_id' => $customer->id,
        'product_variant_id' => $variant->id,
    ]);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->deleteJson("/api/stock-alerts/variants/{$variant->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Stok bildirimi iptal edildi.');

    expect(StockAlert::query()->count())->toBe(0);
});

it('queues a back in stock email when inventory returns from zero', function () {
    Mail::fake();

    $customer = User::factory()->create();
    $variant = createOutOfStockVariant(User::factory()->vendor()->create());

    StockAlert::query()->create([
        'user_id' => $customer->id,
        'product_variant_id' => $variant->id,
    ]);

    $variant->stock?->update(['quantity' => 8]);
    app(BackInStockService::class)->evaluateVariant($variant->fresh(['stock', 'product']), 0);

    Mail::assertQueued(BackInStockMail::class, function (BackInStockMail $mail) use ($customer, $variant) {
        return $mail->hasTo($customer->email)
            && $mail->user->is($customer)
            && $mail->variant->is($variant->fresh());
    });

    expect(StockAlert::query()->first()?->notified_at)->not->toBeNull();
});

it('allows the same customer to be notified again after stock runs out and returns', function () {
    Mail::fake();

    $customer = User::factory()->create();
    $variant = createOutOfStockVariant(User::factory()->vendor()->create());

    StockAlert::query()->create([
        'user_id' => $customer->id,
        'product_variant_id' => $variant->id,
        'notified_at' => now(),
    ]);

    app(BackInStockService::class)->evaluateVariant($variant->fresh(['stock', 'product']), 1);

    expect(StockAlert::query()->first()?->notified_at)->toBeNull();

    $variant->stock?->update(['quantity' => 4]);
    app(BackInStockService::class)->evaluateVariant($variant->fresh(['stock', 'product']), 0);

    Mail::assertQueued(BackInStockMail::class);
});

it('forbids vendors from subscribing to stock alerts', function () {
    $variant = createOutOfStockVariant(User::factory()->vendor()->create());

    $this->withToken(User::factory()->vendor()->create()->createToken('test')->plainTextToken)
        ->postJson("/api/stock-alerts/variants/{$variant->id}")
        ->assertForbidden();
});
