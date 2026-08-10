<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns paginated products from the public products endpoint', function () {
    $vendor = User::factory()->vendor()->create();
    $category = Category::query()->create([
        'name' => 'Elektronik',
        'slug' => 'elektronik',
    ]);

    Product::query()->create([
        'user_id' => $vendor->id,
        'category_id' => $category->id,
        'name' => 'Kulaklık Pro',
        'price' => 1500,
        'description' => 'Test',
    ]);

    Product::query()->create([
        'user_id' => $vendor->id,
        'category_id' => $category->id,
        'name' => 'Hoparlör Mini',
        'price' => 900,
        'description' => 'Test',
    ]);

    $this->getJson('/api/products?per_page=1&page=1')
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonCount(1, 'products');
});

it('filters products by search term and category slug', function () {
    $vendor = User::factory()->vendor()->create();
    $electronics = Category::query()->create(['name' => 'Elektronik', 'slug' => 'elektronik']);
    $home = Category::query()->create(['name' => 'Ev', 'slug' => 'ev']);

    Product::query()->create([
        'user_id' => $vendor->id,
        'category_id' => $electronics->id,
        'name' => 'Bluetooth Kulaklık',
        'price' => 1200,
        'description' => 'Test',
    ]);

    Product::query()->create([
        'user_id' => $vendor->id,
        'category_id' => $home->id,
        'name' => 'Masa Lambası',
        'price' => 400,
        'description' => 'Test',
    ]);

    $this->getJson('/api/products?search=Bluetooth&category=elektronik')
        ->assertOk()
        ->assertJsonCount(1, 'products')
        ->assertJsonPath('products.0.name', 'Bluetooth Kulaklık');
});

it('sorts products by price ascending', function () {
    $vendor = User::factory()->vendor()->create();

    Product::query()->create([
        'user_id' => $vendor->id,
        'name' => 'Pahalı Ürün',
        'price' => 5000,
        'description' => 'Test',
    ]);

    Product::query()->create([
        'user_id' => $vendor->id,
        'name' => 'Ucuz Ürün',
        'price' => 100,
        'description' => 'Test',
    ]);

    $this->getJson('/api/products?sort=price_asc')
        ->assertOk()
        ->assertJsonPath('products.0.name', 'Ucuz Ürün')
        ->assertJsonPath('products.1.name', 'Pahalı Ürün');
});
