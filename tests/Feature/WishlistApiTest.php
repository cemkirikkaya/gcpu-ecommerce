<?php

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createProductForWishlist(User $vendor): Product
{
    return Product::query()->create([
        'user_id' => $vendor->id,
        'name' => 'Favori Ürün',
        'price' => 1200,
        'description' => 'Test',
    ]);
}

it('lists the authenticated customers favorite products', function () {
    $customer = User::factory()->create();
    $vendor = User::factory()->vendor()->create();
    $first = createProductForWishlist($vendor);
    $second = createProductForWishlist($vendor);

    WishlistItem::query()->create([
        'user_id' => $customer->id,
        'product_id' => $first->id,
    ]);
    WishlistItem::query()->create([
        'user_id' => $customer->id,
        'product_id' => $second->id,
    ]);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->getJson('/api/wishlist')
        ->assertOk()
        ->assertJsonCount(2, 'products')
        ->assertJsonFragment(['id' => $first->id])
        ->assertJsonFragment(['id' => $second->id]);
});

it('returns favorite product ids for the authenticated customer', function () {
    $customer = User::factory()->create();
    $vendor = User::factory()->vendor()->create();
    $product = createProductForWishlist($vendor);

    WishlistItem::query()->create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
    ]);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->getJson('/api/wishlist/ids')
        ->assertOk()
        ->assertJsonPath('product_ids', [$product->id]);
});

it('adds a product to the wishlist', function () {
    $customer = User::factory()->create();
    $vendor = User::factory()->vendor()->create();
    $product = createProductForWishlist($vendor);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->postJson("/api/wishlist/products/{$product->id}")
        ->assertCreated()
        ->assertJsonPath('product_id', $product->id);

    expect(WishlistItem::query()->where('user_id', $customer->id)->count())->toBe(1);
});

it('does not duplicate wishlist entries', function () {
    $customer = User::factory()->create();
    $vendor = User::factory()->vendor()->create();
    $product = createProductForWishlist($vendor);

    $token = $customer->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/wishlist/products/{$product->id}")
        ->assertCreated();

    $this->withToken($token)
        ->postJson("/api/wishlist/products/{$product->id}")
        ->assertCreated();

    expect(WishlistItem::query()->where('user_id', $customer->id)->count())->toBe(1);
});

it('removes a product from the wishlist', function () {
    $customer = User::factory()->create();
    $vendor = User::factory()->vendor()->create();
    $product = createProductForWishlist($vendor);

    WishlistItem::query()->create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
    ]);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->deleteJson("/api/wishlist/products/{$product->id}")
        ->assertOk()
        ->assertJsonPath('product_id', $product->id);

    expect(WishlistItem::query()->where('user_id', $customer->id)->count())->toBe(0);
});

it('forbids removing another users wishlist item', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $vendor = User::factory()->vendor()->create();
    $product = createProductForWishlist($vendor);

    WishlistItem::query()->create([
        'user_id' => $owner->id,
        'product_id' => $product->id,
    ]);

    $this->withToken($other->createToken('test')->plainTextToken)
        ->deleteJson("/api/wishlist/products/{$product->id}")
        ->assertNotFound();
});

it('forbids wishlist access for admin users', function () {
    $admin = User::factory()->admin()->create();

    $this->withToken($admin->createToken('test')->plainTextToken)
        ->getJson('/api/wishlist')
        ->assertForbidden();
});
