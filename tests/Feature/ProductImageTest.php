<?php

use App\Models\Image;
use App\Models\Product;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\ProductImageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('returns product cover image url from images table in catalog api', function (): void {
    $product = Product::query()->create([
        'name' => 'Test Phone',
        'price' => 1000,
        'description' => 'Test description',
    ]);

    Image::query()->create([
        'product_id' => $product->id,
        'product_variant_id' => null,
        'image' => 'catalog/products/test-phone.jpg',
        'label' => 'Test Phone',
        'is_cover' => true,
        'sort_order' => 0,
    ]);

    $response = $this->getJson("/api/products/{$product->id}");

    $response
        ->assertSuccessful()
        ->assertJsonPath('product.image_url', '/storage/catalog/products/test-phone.jpg');
});

it('seeds cover and variant images for catalog products', function (): void {
    Http::fake([
        'loremflickr.com/*' => Http::response(
            file_get_contents(base_path('tests/Fixtures/catalog-sample.jpg')),
            200,
            ['Content-Type' => 'image/jpeg'],
        ),
    ]);

    $this->seed([
        CatalogSeeder::class,
        ProductImageSeeder::class,
    ]);

    expect(Image::query()->where('is_cover', true)->count())->toBeGreaterThan(0)
        ->and(Image::query()->whereNotNull('product_variant_id')->count())->toBeGreaterThan(0)
        ->and(file_exists(storage_path('app/public/catalog/products/nova-x-pro-1.jpg')))->toBeTrue();
});
