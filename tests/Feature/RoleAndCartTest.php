<?php

use App\Enums\UserRole;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows only admins to access the filament panel', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    expect($admin->canAccessPanel(filament()->getCurrentOrDefaultPanel()))->toBeTrue()
        ->and($user->canAccessPanel(filament()->getCurrentOrDefaultPanel()))->toBeFalse();
});

it('redirects admins to the admin panel after login', function () {
    $admin = User::factory()->admin()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect('/admin');
});

it('lets customers add different product variants to their cart', function () {
    $user = User::factory()->create([
        'email' => 'shopper@example.com',
        'password' => 'password',
    ]);

    $product = Product::query()->create([
        'name' => 'Telefon',
        'price' => 100,
        'description' => 'Açıklama',
    ]);

    $blackVariant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'PHONE-BLACK',
    ]);

    $whiteVariant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'PHONE-WHITE',
    ]);

    Stock::query()->create(['product_variant_id' => $blackVariant->id, 'quantity' => 10]);
    Stock::query()->create(['product_variant_id' => $whiteVariant->id, 'quantity' => 5]);

    $this->actingAs($user)
        ->post(route('cart.items.store'), [
            'product_variant_id' => $blackVariant->id,
            'quantity' => 2,
        ])
        ->assertRedirect(route('cart.index'));

    $this->actingAs($user)
        ->post(route('cart.items.store'), [
            'product_variant_id' => $whiteVariant->id,
            'quantity' => 1,
        ])
        ->assertRedirect(route('cart.index'));

    expect(CartItem::query()->count())->toBe(2)
        ->and($user->cart->items()->where('product_variant_id', $blackVariant->id)->value('quantity'))->toBe(2);
});

it('can add a product again after checkout soft-deletes the cart item', function () {
    $user = User::factory()->create();

    $product = Product::query()->create([
        'name' => 'Kulaklık',
        'price' => 500,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'HEADPHONE-READD-1',
    ]);

    Stock::query()->create(['product_variant_id' => $variant->id, 'quantity' => 5]);

    app(CartService::class)->addItem($user, $variant, 1);

    $order = app(OrderService::class)->checkout($user);
    app(OrderService::class)->completePayment($order);

    expect(CartItem::query()->count())->toBe(0);

    app(CartService::class)->addItem($user, $variant, 1);

    expect(CartItem::query()->count())->toBe(1)
        ->and($user->cart->items()->where('product_variant_id', $variant->id)->value('quantity'))->toBe(1);
});

it('registers new users with the customer role', function () {
    $this->post(route('register'), [
        'name' => 'Yeni Kullanıcı',
        'email' => 'new-user@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('products.index'));

    expect(User::query()->where('email', 'new-user@example.com')->value('role'))
        ->toBe(UserRole::Customer);
});
