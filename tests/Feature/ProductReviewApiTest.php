<?php

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
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

it('lists product reviews publicly with summary', function () {
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $product = createProductForReview($vendor);

    ProductReview::query()->create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'rating' => 4,
        'comment' => 'Harika bir ürün, memnun kaldım.',
    ]);

    $this->getJson("/api/products/{$product->id}/reviews")
        ->assertOk()
        ->assertJsonPath('summary.count', 1)
        ->assertJsonPath('summary.average', 4)
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
    ]);

    $this->getJson("/api/products/{$product->id}")
        ->assertOk()
        ->assertJsonPath('product.review_summary.count', 1)
        ->assertJsonPath('product.review_summary.average', 5);
});

it('allows a customer to create a product review', function () {
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $product = createProductForReview($vendor);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 5,
            'comment' => 'Çok beğendim, hızlı kargo ve kaliteli ürün.',
        ])
        ->assertCreated()
        ->assertJsonPath('review.rating', 5);

    expect(ProductReview::query()->where('product_id', $product->id)->count())->toBe(1);
});

it('prevents duplicate reviews from the same customer', function () {
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $product = createProductForReview($vendor);

    ProductReview::query()->create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'rating' => 3,
        'comment' => 'İlk yorumum, fena değil aslında.',
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
    ]);

    $this->withToken($other->createToken('test')->plainTextToken)
        ->putJson("/api/products/{$product->id}/reviews/{$review->id}", [
            'rating' => 1,
            'comment' => 'Başkasının yorumunu değiştirmeye çalışıyorum.',
        ])
        ->assertForbidden();
});

it('returns the authenticated customers own review', function () {
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $product = createProductForReview($vendor);
    $review = ProductReview::query()->create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'rating' => 4,
        'comment' => 'Kendi yorumumu görüntülüyorum.',
    ]);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->getJson("/api/products/{$product->id}/reviews/mine")
        ->assertOk()
        ->assertJsonPath('review.id', $review->id);
});
