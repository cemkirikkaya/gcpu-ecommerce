<?php

namespace App\Http\Resources\Api;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\AdminOrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use LogicException;

/** @mixin Order */
class AdminOrderResource extends JsonResource
{
    private ?User $viewer = null;

    /** @var Collection<int, OrderItem>|null */
    private ?Collection $scopedItems = null;

    private ?float $fullOrderTotal = null;

    /**
     * @param  Collection<int, OrderItem>|null  $scopedItems
     */
    public static function forUser(
        Order $order,
        User $viewer,
        ?Collection $scopedItems = null,
        ?float $fullOrderTotal = null,
    ): self {
        $resource = new self($order);
        $resource->viewer = $viewer;
        $resource->scopedItems = $scopedItems;
        $resource->fullOrderTotal = $fullOrderTotal ?? (float) $order->getRawOriginal('total_price');

        return $resource;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $this->viewer ?? $request->user();

        if (! $viewer instanceof User) {
            throw new LogicException('Admin order resource requires an authenticated user.');
        }

        /** @var Order $order */
        $order = $this->resource;

        $service = app(AdminOrderService::class);
        $items = $this->scopedItems ?? $service->itemsForUser($order, $viewer);
        $vendorSubtotal = $service->vendorSubtotal($items);
        $isVendorView = $viewer->isVendor();
        $fullOrderTotal = $this->fullOrderTotal ?? (float) $order->getRawOriginal('total_price');

        return [
            'id' => $order->id,
            'total_price' => $isVendorView ? $vendorSubtotal : $fullOrderTotal,
            'order_total' => $isVendorView ? $fullOrderTotal : null,
            'vendor_subtotal' => $isVendorView ? $vendorSubtotal : null,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'payment_status' => $order->payment_status->value,
            'payment_status_label' => $order->payment_status->label(),
            'created_at' => $order->created_at?->toIso8601String(),
            'items_count' => $items->count(),
            'items' => $this->when(
                $order->relationLoaded('items'),
                fn () => $items->map(function (OrderItem $item): array {
                    $variant = $item->cartItem?->productVariant;
                    $product = $variant?->product;

                    return [
                        'id' => $item->id,
                        'quantity' => $item->quantity,
                        'price' => (float) $item->price,
                        'subtotal' => $item->subtotal(),
                        'product_name' => $product?->name,
                        'variant_label' => $variant?->displayLabel() ?: null,
                        'vendor_email' => $product?->vendor?->email,
                    ];
                })->values(),
            ),
            ...($viewer->isAdmin() ? [
                'address' => new AddressResource($this->whenLoaded('address')),
            ] : []),
        ];
    }
}
