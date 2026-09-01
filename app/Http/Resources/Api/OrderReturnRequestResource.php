<?php

namespace App\Http\Resources\Api;

use App\Models\OrderReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderReturnRequest */
class OrderReturnRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'message' => $this->message,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'admin_note' => $this->admin_note,
            'refund_reference' => $this->refund_reference,
            'refund_amount' => $this->refund_amount !== null ? (float) $this->refund_amount : null,
            'return_tracking_number' => $this->return_tracking_number,
            'return_tracking_url' => $this->return_tracking_url,
            'return_label_url' => $this->return_label_url,
            'exchange_tracking_number' => $this->exchange_tracking_number,
            'exchange_tracking_url' => $this->exchange_tracking_url,
            'created_at' => $this->created_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'received_at' => $this->received_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'customer' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(function ($item): array {
                $orderItem = $item->orderItem;
                $variant = $orderItem?->cartItem?->productVariant;
                $replacement = $item->replacementProductVariant;

                return [
                    'id' => $item->id,
                    'order_item_id' => $item->order_item_id,
                    'quantity' => $item->quantity,
                    'product_name' => $variant?->product?->name,
                    'variant_label' => $variant?->displayLabel() ?: null,
                    'replacement_product_variant_id' => $item->replacement_product_variant_id,
                    'replacement_variant_label' => $replacement?->displayLabel() ?: null,
                ];
            })->values()),
            'order' => $this->whenLoaded('order', fn (): array => [
                'id' => $this->order->id,
                'total_price' => (float) $this->order->total_price,
                'status' => $this->order->status->value,
                'status_label' => $this->order->status->label(),
                'payment_status' => $this->order->payment_status->value,
                'payment_status_label' => $this->order->payment_status->label(),
            ]),
        ];
    }
}
