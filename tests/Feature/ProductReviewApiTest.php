<?php

use App\Enums\OrderStatus;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createProductForReview(User $vendor): Product
{
    return Product::query()->create([
        'user_id' => $vendor->id,
        'name' => 'Yorumlu Ürün',
        'price' => 1500,
        'description' => 'Test',
    ]);
}

function createDeliveredPurchaseForReview(User $customer, Product $product): void
{
    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'REVIEW-'.fake()->unique()->bothify('??-####'),
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 5,
    ]);

    app(CartService::class)->addItem($customer, $variant, 1);

    $order = app(OrderService::class)->checkout($customer);
    app(OrderService::class)->chargePaymentDirectly($order, '127.0.0.1');
    $order->update(['status' => OrderStatus::Delivered]);
}

it('lists product reviews publicly with summary', function () {
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $product = createProductForReview($vendor);

    ProductReview::query()->create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'rating' => 4,
        'comment' => 'Harika bir ürün, memnun kaldım.',
        'is_verified_purchase' => true,
    ]);

    $this->getJson("/api/products/{$product->id}/reviews")
        ->assertOk()
        ->assertJsonPath('summary.count', 1)
        ->assertJsonPath('summary.average', 4)
        ->assertJsonPath('reviews.0.is_verified_purchase', true)
        ->assertJsonCount(1, 'reviews');
});

it('includes review summary on product detail', function () {
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $product = createProductForReview($vendor);

    ProductReview::query()->create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'rating' => 5,
        'comment' => 'Kesinlikle tavsiye ederim, çok kaliteli.',
        'is_verified_purchase' => true,
    ]);

    $this->getJson("/api/products/{$product->id}")
        ->assertOk()
        ->assertJsonPath('product.review_summary.count', 1)
        ->assertJsonPath('product.review_summary.average', 5);
});

it('allows a customer with a delivered purchase to create a product review', function () {
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $product = createProductForReview($vendor);
    createDeliveredPurchaseForReview($customer, $product);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 5,
            'comment' => 'Çok beğendim, hızlı kargo ve kaliteli ürün.',
        ])
        ->assertCreated()
        ->assertJsonPath('review.rating', 5)
        ->assertJsonPath('review.is_verified_purchase', true);

    expect(ProductReview::query()->where('product_id', $product->id)->count())->toBe(1);
});

it('prevents reviews from customers who have not received the product', function () {
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $product = createProductForReview($vendor);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 5,
            'comment' => 'Satın almadan yorum yazmaya çalışıyorum.',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Yorum yazmak için bu ürünü satın alıp teslim almış olmanız gerekir.');

    expect(ProductReview::query()->where('product_id', $product->id)->count())->toBe(0);
});

it('prevents reviews from customers with only a paid but undelivered order', function () {
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $product = createProductForReview($vendor);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'REVIEW-PAID-ONLY',
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 5,
    ]);

    app(CartService::class)->addItem($customer, $variant, 1);
    $order = app(OrderService::class)->checkout($customer);
    app(OrderService::class)->chargePaymentDirectly($order, '127.0.0.1');

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 4,
            'comment' => 'Ödeme yaptım ama henüz teslim almadım.',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Yorum yazmak için bu ürünü satın alıp teslim almış olmanız gerekir.');
});

it('prevents duplicate reviews from the same customer', function () {
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $product = createProductForReview($vendor);
    createDeliveredPurchaseForReview($customer, $product);

    ProductReview::query()->create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'rating' => 3,
        'comment' => 'İlk yorumum, fena değil aslında.',
        'is_verified_purchase' => true,
    ]);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 5,
            'comment' => 'Tekrar yorum yazmak istiyorum ama olmaz.',
        ])
        ->assertUnprocessable();
});

it('updates an owned review', function () {
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $product = createProductForReview($vendor);
    $review = ProductReview::query()->create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'rating' => 3,
        'comment' => 'Orta seviye bir deneyim yaşadım.',
        'is_verified_purchase' => true,
    ]);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->putJson("/api/products/{$product->id}/reviews/{$review->id}", [
            'rating' => 4,
            'comment' => 'Bir süre kullandıktan sonra daha iyi buldum.',
        ])
        ->assertOk()
        ->assertJsonPath('review.rating', 4);
});

it('deletes an owned review', function () {
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $product = createProductForReview($vendor);
    $review = ProductReview::query()->create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'rating' => 2,
        'comment' => 'Beklentimi karşılamadı maalesef.',
        'is_verified_purchase' => true,
    ]);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->deleteJson("/api/products/{$product->id}/reviews/{$review->id}")
        ->assertOk();

    expect(ProductReview::query()->find($review->id))->toBeNull();
});

it('forbids updating another users review', function () {
    $vendor = User::factory()->vendor()->create();
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $product = createProductForReview($vendor);
    $review = ProductReview::query()->create([
        'user_id' => $owner->id,
        'product_id' => $product->id,
        'rating' => 5,
        'comment' => 'Sahibinin yorumu, çok memnun kaldım.',
        'is_verified_purchase' => true,
    ]);

    $this->withToken($other->createToken('test')->plainTextToken)
        ->putJson("/api/products/{$product->id}/reviews/{$review->id}", [
            'rating' => 1,
            'comment' => 'Başkasının yorumunu değiştirmeye çalışıyorum.',
        ])
        ->assertForbidden();
});

it('returns the authenticated customers own review and review eligibility', function () {
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $product = createProductForReview($vendor);
    createDeliveredPurchaseForReview($customer, $product);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->getJson("/api/products/{$product->id}/reviews/mine")
        ->assertOk()
        ->assertJsonPath('review', null)
        ->assertJsonPath('can_review', true);
});

it('returns can_review false when the customer already reviewed the product', function () {
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $product = createProductForReview($vendor);
    createDeliveredPurchaseForReview($customer, $product);

    $review = ProductReview::query()->create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'rating' => 4,
        'comment' => 'Kendi yorumumu görüntülüyorum.',
        'is_verified_purchase' => true,
    ]);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->getJson("/api/products/{$product->id}/reviews/mine")
        ->assertOk()
        ->assertJsonPath('review.id', $review->id)
        ->assertJsonPath('can_review', false);
});
