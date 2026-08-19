<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockAlertController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function variantIds(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockAlert::class);

        $variantIds = $request->user()
            ->stockAlerts()
            ->pluck('product_variant_id');

        return response()->json([
            'variant_ids' => $variantIds,
        ]);
    }

    public function store(Request $request, ProductVariant $variant): JsonResponse
    {
        $this->authorize('create', StockAlert::class);

        $variant->loadMissing('stock');

        if ($this->stockService->availableQuantity($variant) > 0) {
            return response()->json([
                'message' => 'Bu varyant şu an stokta.',
            ], 422);
        }

        StockAlert::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'product_variant_id' => $variant->id,
            ],
            [
                'notified_at' => null,
            ],
        );

        return response()->json([
            'message' => 'Stoğa dönünce e-posta ile bilgilendirileceksiniz.',
            'variant_id' => $variant->id,
        ], 201);
    }

    public function destroy(Request $request, ProductVariant $variant): JsonResponse
    {
        $stockAlert = StockAlert::query()
            ->where('user_id', $request->user()->id)
            ->where('product_variant_id', $variant->id)
            ->firstOrFail();

        $this->authorize('delete', $stockAlert);

        $stockAlert->delete();

        return response()->json([
            'message' => 'Stok bildirimi iptal edildi.',
            'variant_id' => $variant->id,
        ]);
    }
}
