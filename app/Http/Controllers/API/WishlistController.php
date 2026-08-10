<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductResource;
use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WishlistItem::class);

        $products = $request->user()
            ->favoriteProducts()
            ->tap(fn (Builder $query) => $this->withCatalogRelations($query))
            ->orderByPivot('created_at', 'desc')
            ->get();

        return response()->json([
            'products' => ProductResource::collection($products),
        ]);
    }

    public function ids(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WishlistItem::class);

        $productIds = $request->user()
            ->wishlistItems()
            ->pluck('product_id');

        return response()->json([
            'product_ids' => $productIds,
        ]);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $this->authorize('create', WishlistItem::class);

        WishlistItem::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);

        return response()->json([
            'message' => 'Favorilere eklendi.',
            'product_id' => $product->id,
        ], 201);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $wishlistItem = WishlistItem::query()
            ->where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->firstOrFail();

        $this->authorize('delete', $wishlistItem);

        $wishlistItem->delete();

        return response()->json([
            'message' => 'Favorilerden çıkarıldı.',
            'product_id' => $product->id,
        ]);
    }

    private function withCatalogRelations(Builder $query): void
    {
        $query->with([
            'baseVariant',
            'category.parent',
            'variants.stock',
            'variants.images',
            'variants.product',
            'variants.variantValues.variantValue.variant',
            'images',
            'media',
        ]);
    }
}
