<?php

namespace App\Http\Resources\Api;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Cart */
class CartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_count' => $this->items->sum('quantity'),
            'subtotal' => $this->subtotal(),
            'discount_amount' => $this->discountAmount(),
            'total' => $this->total(),
            'coupon' => $this->whenLoaded('coupon', fn () => $this->coupon ? [
                'id' => $this->coupon->id,
                'code' => $this->coupon->code,
                'type' => $this->coupon->type->value,
                'value' => (float) $this->coupon->value,
            ] : null),
            'reservation_minutes' => config('shop.reservation_minutes'),
            'items' => CartItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
