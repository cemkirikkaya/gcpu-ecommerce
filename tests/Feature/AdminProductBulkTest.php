<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function bulkCsvFile(string $name, string $contents): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $contents);
}

it('imports products from csv for admin users', function () {
    Category::query()->create([
        'name' => 'Elektronik',
        'slug' => 'elektronik',
    ]);

    $csv = <<<'CSV'
name,description,price,category,sku,stock,color
Kulaklık Pro,Bluetooth kulaklık,1299.90,elektronik,KUL-001,20,Siyah
Kulaklık Pro,Bluetooth kulaklık,1299.90,elektronik,KUL-002,15,Beyaz
CSV;

    $this->withToken(User::factory()->admin()->create()->createToken('test')->plainTextToken)
        ->post('/api/admin/products/bulk/import', [
            'file' => bulkCsvFile('products.csv', $csv),
        ])
        ->assertOk()
        ->assertJsonPath('result.created', 1)
        ->assertJsonPath('result.merged', 0)
        ->assertJsonPath('result.errors', []);

    $product = Product::query()->where('name', 'Kulaklık Pro')->first();

    expect($product)->not->toBeNull()
        ->and($product?->price)->toBe('1299.90')
        ->and(ProductVariant::query()->where('product_id', $product?->id)->count())->toBe(2)
        ->and(Stock::query()->whereHas('productVariant', fn ($query) => $query->where('sku', 'KUL-001'))->value('quantity'))->toBe(20);
});

it('updates product price and stock from csv by sku', function () {
    $product = Product::query()->create([
        'name' => 'Güncellenecek Ürün',
        'price' => 500,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'UPD-001',
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 4,
    ]);

    $csv = <<<'CSV'
sku,price,stock
UPD-001,699.50,12
CSV;

    $this->withToken(User::factory()->admin()->create()->createToken('test')->plainTextToken)
        ->post('/api/admin/products/bulk/update', [
            'file' => bulkCsvFile('updates.csv', $csv),
        ])
        ->assertOk()
        ->assertJsonPath('result.updated', 1)
        ->assertJsonPath('result.errors', []);

    expect($product->fresh()?->price)->toBe('699.50')
        ->and($variant->fresh()?->stock?->quantity)->toBe(12);
});

it('forbids vendors from updating another vendors sku', function () {
    $owner = User::factory()->vendor()->create();
    $otherVendor = User::factory()->vendor()->create();

    $product = Product::query()->create([
        'user_id' => $owner->id,
        'name' => 'Vendor Ürün',
        'price' => 300,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'VENDOR-001',
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $csv = "sku,stock\nVENDOR-001,10\n";

    $this->withToken($otherVendor->createToken('test')->plainTextToken)
        ->post('/api/admin/products/bulk/update', [
            'file' => bulkCsvFile('updates.csv', $csv),
        ])
        ->assertOk()
        ->assertJsonPath('result.updated', 0)
        ->assertJsonPath('result.errors.0.message', 'SKU VENDOR-001 için güncelleme yetkiniz yok.');
});

it('downloads bulk csv templates', function () {
    $this->withToken(User::factory()->admin()->create()->createToken('test')->plainTextToken)
        ->get('/api/admin/products/bulk/template/import')
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $this->withToken(User::factory()->admin()->create()->createToken('test')->plainTextToken)
        ->get('/api/admin/products/bulk/template/update')
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('validates csv file on bulk import', function () {
    $this->withToken(User::factory()->admin()->create()->createToken('test')->plainTextToken)
        ->post('/api/admin/products/bulk/import', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);
});
