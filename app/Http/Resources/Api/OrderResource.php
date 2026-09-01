<?php

namespace App\Http\Resources\Api;

use App\Models\Order;
use App\Services\OrderReturnService;
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
            'subtotal' => $this->subtotal !== null ? (float) $this->subtotal : null,
            'discount_amount' => (float) ($this->discount_amount ?? 0),
            'coupon_code' => $this->coupon_code,
            'total_price' => (float) $this->total_price,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            'payment_provider' => $this->paymentProvider(),
            'installment' => $this->installment,
            'paid_price' => $this->paid_price !== null ? (float) $this->paid_price : null,
            'iyzico_payment_id' => $this->iyzico_payment_id,
            'stripe_checkout_session_id' => $this->stripe_checkout_session_id,
            'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'tracking_number' => $this->tracking_number,
            'tracking_url' => $this->trackingPageUrl(),
            'estimated_delivery_at' => $this->estimated_delivery_at?->toIso8601String(),
            'can_download_invoice' => $request->user()?->can('downloadInvoice', $this->resource) ?? false,
            'return_window_days' => (int) config('shop.return_window_days', 14),
            'address' => new AddressResource($this->whenLoaded('address')),
            'cancellation_request' => new OrderCancellationRequestResource(
                $this->whenLoaded('latestCancellationRequest'),
            ),
            'return_requests' => OrderReturnRequestResource::collection(
                $this->whenLoaded('returnRequests'),
            ),
            'items' => $this->whenLoaded('items', function () {
                $returnService = app(OrderReturnService::class);
                $committed = $returnService->committedQuantitiesByOrderItemId($this->resource);

                return $this->items->map(function ($item) use ($committed): array {
                    $variant = $item->cartItem?->productVariant;
                    $product = $variant?->product;
                    $returnableQuantity = max(0, $item->quantity - ($committed[$item->id] ?? 0));

                    return [
                        'id' => $item->id,
                        'quantity' => $item->quantity,
                        'price' => (float) $item->price,
                        'subtotal' => $item->subtotal(),
                        'product_name' => $product?->name,
                        'variant_label' => $variant?->displayLabel() ?: null,
                        'product_id' => $product?->id,
                        'product_variant_id' => $variant?->id,
                        'returnable_quantity' => $returnableQuantity,
                        'exchange_variants' => $product?->relationLoaded('variants')
                            ? $product->variants->map(fn ($exchangeVariant): array => [
                                'id' => $exchangeVariant->id,
                                'label' => $exchangeVariant->displayLabel() ?: $exchangeVariant->sku,
                            ])->values()
                            : [],
                    ];
                });
            }),
        ];
    }
}
