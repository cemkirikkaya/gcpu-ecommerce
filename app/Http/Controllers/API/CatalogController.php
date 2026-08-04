<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CategoryResource;
use App\Http\Resources\Api\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    public function __construct(
        private ProductService $productService,
    ) {}

    public function index(): JsonResponse
    {
        $categories = $this->productService->getGroupedByCategory();
        $uncategorized = $this->productService->getUncategorized();

        return response()->json([
            'shop_name' => config('shop.name'),
            'reservation_minutes' => config('shop.reservation_minutes'),
            'categories' => CategoryResource::collection($categories),
            'uncategorized' => ProductResource::collection($uncategorized),
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load([
            'category.parent',
            'baseVariant',
            'variants.stock',
            'variants.images',
            'variants.variantValues.variantValue.variant',
            'variants.product',
            'images',
            'media',
        ]);

        return response()->json([
            'product' => new ProductResource($product),
        ]);
    }
}
