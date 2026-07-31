<?php

namespace App\Http\Resources\Api;

use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CartItem */
class CartItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $variant = $this->productVariant;
        $product = $variant?->product;

        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'reserved_until' => $this->reserved_until?->toIso8601String(),
            'unit_price' => (float) ($product?->price ?? 0),
            'subtotal' => $this->subtotal(),
            'variant' => $variant !== null
                ? new ProductVariantResource($variant)
                : null,
        ];
    }
}
