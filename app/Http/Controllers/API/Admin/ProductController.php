<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Resources\Api\AdminProductResource;
use App\Models\Product;
use App\Services\ProductCatalogService;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(private ProductCatalogService $catalogService) {}

    public function index(): JsonResponse
    {
        $products = Product::query()
            ->with([
                'category',
                'variants.stock',
                'variants.variantValues.variantValue.variant',
            ])
            ->latest()
            ->get();

        return response()->json([
            'products' => AdminProductResource::collection($products),
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load([
            'category',
            'variants.stock',
            'variants.variantValues.variantValue.variant',
        ]);

        return response()->json([
            'product' => new AdminProductResource($product),
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $result = $this->catalogService->storeOrMergeProduct($request->validated());

        $result['product']->load([
            'category',
            'variants.stock',
            'variants.variantValues.variantValue.variant',
        ]);

        if ($result['merged']) {
            return response()->json([
                'product' => new AdminProductResource($result['product']),
                'merged' => true,
                'message' => 'Aynı isimde ürün bulundu. Varyantlar mevcut ürüne eklendi.',
            ]);
        }

        return response()->json([
            'product' => new AdminProductResource($result['product']),
            'merged' => false,
            'message' => 'Ürün başarıyla oluşturuldu.',
        ], 201);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $validated = $request->validated();

        if (array_key_exists('catalog_variants', $validated)) {
            $catalogVariants = $validated['catalog_variants'];
            unset($validated['catalog_variants']);
            $this->catalogService->syncVariants($product, $catalogVariants);
        }

        if ($validated !== []) {
            $product->update($validated);
        }

        $product->load([
            'category',
            'variants.stock',
            'variants.variantValues.variantValue.variant',
        ]);

        return response()->json([
            'product' => new AdminProductResource($product),
            'message' => 'Ürün güncellendi.',
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->catalogService->deleteProduct($product);

        return response()->json([
            'message' => 'Ürün silindi.',
        ]);
    }
}
