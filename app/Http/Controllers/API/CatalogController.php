<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\ListProductsRequest;
use App\Http\Resources\Api\CategoryDetailResource;
use App\Http\Resources\Api\CategoryResource;
use App\Http\Resources\Api\ProductResource;
use App\Models\Category;
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

    public function category(Category $category): JsonResponse
    {
        $category->load([
            'parent',
            'children' => fn ($query) => $query->orderBy('name')->withCount('products'),
        ]);

        $subtreeIds = Category::idsInSubtree($category->id);
        $category->products_count_in_subtree = Product::query()
            ->whereIn('category_id', $subtreeIds)
            ->count();

        return response()->json([
            'category' => new CategoryDetailResource($category),
        ]);
    }

    public function products(ListProductsRequest $request): JsonResponse
    {
        $products = $this->productService->listFiltered($request->validated());

        $filterCategories = Category::query()
            ->whereHas('products')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'products' => ProductResource::collection($products->items()),
            'categories' => $filterCategories->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ]),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
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
        $product->loadAvg('reviews', 'rating');
        $product->loadCount('reviews');

        return response()->json([
            'product' => new ProductResource($product),
        ]);
    }
}
