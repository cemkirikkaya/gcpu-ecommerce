<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStockRequest;
use App\Models\Stock;
use App\Services\BackInStockService;
use App\Services\LowStockService;
use Illuminate\Http\JsonResponse;

class StockController extends Controller
{
    public function __construct(
        private LowStockService $lowStockService,
        private BackInStockService $backInStockService,
    ) {}

    public function update(UpdateStockRequest $request, Stock $stock): JsonResponse
    {
        $stock->load('productVariant.product');

        $product = $stock->productVariant?->product;

        if ($product === null) {
            abort(404);
        }

        $this->authorize('update', $product);

        $previousQuantity = $stock->quantity;

        $stock->update([
            'quantity' => $request->validated('quantity'),
        ]);

        $stock->refresh();
        $stock->productVariant?->unsetRelation('stock');
        $stock->load('productVariant.product.vendor');
        $this->lowStockService->evaluateVariant($stock->productVariant, $previousQuantity);
        $this->backInStockService->evaluateVariant($stock->productVariant, $previousQuantity);

        return response()->json([
            'stock' => [
                'id' => $stock->id,
                'quantity' => $stock->quantity,
                'variant_id' => $stock->product_variant_id,
                'sku' => $stock->productVariant?->sku,
                'product_name' => $stock->productVariant?->product?->name,
                'available_quantity' => $stock->productVariant?->availableQuantity() ?? 0,
            ],
            'message' => 'Stok güncellendi.',
        ]);
    }
}
