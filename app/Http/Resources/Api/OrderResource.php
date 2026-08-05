<?php

namespace App\Http\Resources\Api;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'total_price' => (float) $this->total_price,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            'installment' => $this->installment,
            'paid_price' => $this->paid_price !== null ? (float) $this->paid_price : null,
            'iyzico_payment_id' => $this->iyzico_payment_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'address' => new AddressResource($this->whenLoaded('address')),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(function ($item): array {
                $variant = $item->cartItem?->productVariant;
                $product = $variant?->product;

                return [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'price' => (float) $item->price,
                    'subtotal' => $item->subtotal(),
                    'product_name' => $product?->name,
                    'variant_label' => $variant?->displayLabel() ?: null,
                ];
            })),
        ];
    }
}
