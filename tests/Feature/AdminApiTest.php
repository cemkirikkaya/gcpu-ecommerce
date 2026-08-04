<?php

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Services\ProductCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function adminToken(): string
{
    $admin = User::factory()->admin()->create();

    return $admin->createToken('test')->plainTextToken;
}

it('registers company accounts as admin role', function () {
    $this->postJson('/api/auth/register', [
        'name' => 'Şirket A.Ş.',
        'email' => 'sirket@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'account_type' => 'company',
    ])
        ->assertCreated()
        ->assertJsonPath('user.role', UserRole::Admin->value);

    $this->assertDatabaseHas('users', [
        'email' => 'sirket@example.com',
        'role' => UserRole::Admin->value,
    ]);
});

it('registers customer accounts as customer role', function () {
    $this->postJson('/api/auth/register', [
        'name' => 'Müşteri',
        'email' => 'musteri@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'account_type' => 'customer',
    ])
        ->assertCreated()
        ->assertJsonPath('user.role', UserRole::Customer->value);
});

it('forbids non-admin users from admin api', function () {
    $customer = User::factory()->create();
    $token = $customer->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/admin/products')
        ->assertForbidden();
});

it('lists products for admin users', function () {
    Product::query()->create([
        'name' => 'Test Ürün',
        'price' => 100,
        'description' => 'Test',
    ]);

    $this->withToken(adminToken())
        ->getJson('/api/admin/products')
        ->assertOk()
        ->assertJsonPath('products.0.name', 'Test Ürün');
});

it('creates a product with variants and stock for admin users', function () {
    $category = Category::query()->create([
        'name' => 'Elektronik',
        'slug' => 'elektronik',
    ]);

    $response = $this->withToken(adminToken())
        ->postJson('/api/admin/products', [
            'name' => 'Yeni Kulaklık',
            'description' => 'Test açıklama',
            'price' => 999.99,
            'category_id' => $category->id,
            'catalog_variants' => [
                [
                    'sku' => 'HP-001',
                    'stock' => 15,
                    'color' => 'Siyah',
                ],
            ],
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('product.name', 'Yeni Kulaklık')
        ->assertJsonPath('product.variants.0.sku', 'HP-001')
        ->assertJsonPath('product.variants.0.quantity', 15);

    $this->assertDatabaseHas('products', ['name' => 'Yeni Kulaklık']);
    $this->assertDatabaseHas('stocks', ['quantity' => 15]);
});

it('updates product variants without sku unique conflicts', function () {
    $product = Product::query()->create([
        'name' => 'Tişört',
        'price' => 349.90,
        'description' => 'Test',
    ]);

    app(ProductCatalogService::class)->syncVariants($product, [
        ['sku' => 'TEE-BLACK-M', 'stock' => 20, 'color' => 'Siyah'],
        ['sku' => 'TEE-BLACK-L', 'stock' => 15, 'color' => 'Siyah'],
    ]);

    $this->withToken(adminToken())
        ->putJson("/api/admin/products/{$product->id}", [
            'name' => 'Tişört Güncel',
            'catalog_variants' => [
                ['sku' => 'TEE-BLACK-M', 'stock' => 18, 'color' => 'Siyah'],
                ['sku' => 'TEE-BLACK-L', 'stock' => 12, 'color' => 'Siyah'],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('product.name', 'Tişört Güncel')
        ->assertJsonPath('product.variants.0.quantity', 18);

    $this->assertDatabaseHas('stocks', ['quantity' => 18]);
});

it('merges variants into existing product when name matches', function () {
    $category = Category::query()->create([
        'name' => 'Telefonlar',
        'slug' => 'telefonlar',
    ]);

    $token = adminToken();

    $this->withToken($token)
        ->postJson('/api/admin/products', [
            'name' => 'iPhone 17',
            'description' => 'Apple akıllı telefon',
            'price' => 79999.99,
            'category_id' => $category->id,
            'catalog_variants' => [
                [
                    'sku' => 'IPH17-BLK-256',
                    'stock' => 10,
                    'color' => 'Siyah',
                    'memory' => '256GB',
                ],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('merged', false);

    $this->withToken($token)
        ->postJson('/api/admin/products', [
            'name' => 'iPhone 17',
            'description' => 'Apple akıllı telefon',
            'price' => 79999.99,
            'category_id' => $category->id,
            'catalog_variants' => [
                [
                    'sku' => 'IPH17-WHT-256',
                    'stock' => 8,
                    'color' => 'Beyaz',
                    'memory' => '256GB',
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('merged', true)
        ->assertJsonCount(2, 'product.variants');

    $this->assertDatabaseCount('products', 1);
    $this->assertDatabaseHas('product_variants', ['sku' => 'IPH17-BLK-256']);
    $this->assertDatabaseHas('product_variants', ['sku' => 'IPH17-WHT-256']);
});

it('rejects duplicate skus when creating products', function () {
    $product = Product::query()->create([
        'name' => 'Mevcut Ürün',
        'price' => 100,
        'description' => 'Test',
    ]);
    $product->variants()->create(['sku' => 'DUPLICATE-SKU']);

    $this->withToken(adminToken())
        ->postJson('/api/admin/products', [
            'name' => 'Yeni Ürün',
            'price' => 200,
            'catalog_variants' => [
                ['sku' => 'DUPLICATE-SKU', 'stock' => 5],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['catalog_variants.0.sku']);
});

it('allows reusing sku after product is deleted', function () {
    $token = adminToken();

    $response = $this->withToken($token)
        ->postJson('/api/admin/products', [
            'name' => 'Geçici Ürün',
            'price' => 100,
            'catalog_variants' => [
                ['sku' => 'REUSE-SKU-001', 'stock' => 5],
            ],
        ])
        ->assertCreated();

    $productId = $response->json('product.id');

    $this->withToken($token)
        ->deleteJson("/api/admin/products/{$productId}")
        ->assertOk();

    $this->withToken($token)
        ->postJson('/api/admin/products', [
            'name' => 'Yeni Ürün',
            'price' => 200,
            'catalog_variants' => [
                ['sku' => 'REUSE-SKU-001', 'stock' => 3],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('product.variants.0.sku', 'REUSE-SKU-001');

    $this->assertDatabaseHas('product_variants', [
        'sku' => 'REUSE-SKU-001',
        'deleted_at' => null,
    ]);
});

it('updates stock quantity for admin users', function () {
    $product = Product::query()->create([
        'name' => 'Stok Test',
        'price' => 100,
        'description' => 'Test',
    ]);
    $variant = $product->variants()->create(['sku' => 'SKU-1']);
    $stock = Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 5,
    ]);

    $this->withToken(adminToken())
        ->patchJson("/api/admin/stocks/{$stock->id}", [
            'quantity' => 25,
        ])
        ->assertOk()
        ->assertJsonPath('stock.quantity', 25);

    $this->assertDatabaseHas('stocks', [
        'id' => $stock->id,
        'quantity' => 25,
    ]);
});

it('uploads a product cover image for admin users', function () {
    $product = Product::query()->create([
        'name' => 'Kapak Test',
        'price' => 100,
        'description' => 'Test',
    ]);

    $this->withToken(adminToken())
        ->post('/api/admin/products/'.$product->id.'/cover-image', [
            'image' => UploadedFile::fake()->image('cover.jpg', 800, 1000),
        ])
        ->assertOk()
        ->assertJsonPath('product.image_url', '/storage/catalog/products/kapak-test-'.$product->id.'.jpg');

    $this->assertDatabaseHas('images', [
        'product_id' => $product->id,
        'is_cover' => true,
    ]);
});
