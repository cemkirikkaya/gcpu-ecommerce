<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns category details by slug', function () {
    $parent = Category::query()->create([
        'name' => 'Elektronik',
        'slug' => 'elektronik',
        'description' => 'Elektronik ürünler',
    ]);

    Category::query()->create([
        'parent_id' => $parent->id,
        'name' => 'Telefonlar',
        'slug' => 'telefonlar',
        'description' => 'Akıllı telefonlar',
    ]);

    $this->getJson('/api/categories/elektronik')
        ->assertOk()
        ->assertJsonPath('category.name', 'Elektronik')
        ->assertJsonPath('category.slug', 'elektronik')
        ->assertJsonPath('category.description', 'Elektronik ürünler')
        ->assertJsonCount(1, 'category.children')
        ->assertJsonPath('category.children.0.slug', 'telefonlar');
});

it('returns not found for unknown category slug', function () {
    $this->getJson('/api/categories/olmayan-kategori')
        ->assertNotFound();
});

it('includes descendant products when filtering by parent category slug', function () {
    $vendor = User::factory()->vendor()->create();
    $parent = Category::query()->create(['name' => 'Elektronik', 'slug' => 'elektronik']);
    $child = Category::query()->create([
        'parent_id' => $parent->id,
        'name' => 'Kulaklıklar',
        'slug' => 'kulakliklar',
    ]);

    Product::query()->create([
        'user_id' => $vendor->id,
        'category_id' => $child->id,
        'name' => 'Bluetooth Kulaklık',
        'price' => 1200,
        'description' => 'Test',
    ]);

    $this->getJson('/api/products?category=elektronik')
        ->assertOk()
        ->assertJsonCount(1, 'products')
        ->assertJsonPath('products.0.name', 'Bluetooth Kulaklık');

    $this->getJson('/api/categories/elektronik')
        ->assertOk()
        ->assertJsonPath('category.products_count', 1);
});
