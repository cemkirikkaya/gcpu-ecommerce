<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns product suggestions for autocomplete', function () {
    $vendor = User::factory()->vendor()->create();
    $category = Category::query()->create([
        'name' => 'Elektronik',
        'slug' => 'elektronik',
    ]);

    Product::query()->create([
        'user_id' => $vendor->id,
        'category_id' => $category->id,
        'name' => 'Bluetooth Kulaklık',
        'price' => 1200,
        'description' => 'Test',
    ]);

    Product::query()->create([
        'user_id' => $vendor->id,
        'category_id' => $category->id,
        'name' => 'Masa Lambası',
        'price' => 400,
        'description' => 'Test',
    ]);

    $this->getJson('/api/products/search/suggest?q=bluetooth')
        ->assertOk()
        ->assertJsonCount(1, 'suggestions')
        ->assertJsonPath('suggestions.0.name', 'Bluetooth Kulaklık');
});

it('validates autocomplete query length', function () {
    $this->getJson('/api/products/search/suggest?q=a')
        ->assertUnprocessable();
});

it('returns popular searches ordered by count', function () {
    SearchQuery::query()->create([
        'term' => 'kulaklık',
        'count' => 12,
        'last_searched_at' => now()->subDay(),
    ]);

    SearchQuery::query()->create([
        'term' => 'hoparlör',
        'count' => 5,
        'last_searched_at' => now(),
    ]);

    $this->getJson('/api/products/search/popular')
        ->assertOk()
        ->assertJsonPath('popular.0.term', 'kulaklık')
        ->assertJsonPath('popular.1.term', 'hoparlör');
});

it('records search terms when filtering products', function () {
    $vendor = User::factory()->vendor()->create();

    Product::query()->create([
        'user_id' => $vendor->id,
        'name' => 'Test Ürün',
        'price' => 100,
        'description' => 'Test',
    ]);

    $this->getJson('/api/products?search=Test%20Ürün')
        ->assertOk();

    expect(SearchQuery::query()->where('term', 'test ürün')->value('count'))->toBe(1);

    $this->getJson('/api/products?search=Test%20Ürün')
        ->assertOk();

    expect(SearchQuery::query()->where('term', 'test ürün')->value('count'))->toBe(2);
});
