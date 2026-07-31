<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStockRequest;
use App\Models\Stock;
use Illuminate\Http\JsonResponse;

class StockController extends Controller
{
    public function update(UpdateStockRequest $request, Stock $stock): JsonResponse
    {
        $stock->update([
            'quantity' => $request->validated('quantity'),
        ]);

        $stock->load('productVariant.product');

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
