<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\Stock;
use Illuminate\Support\Carbon;

class StockService
{
    public function __construct(
        private LowStockService $lowStockService,
        private BackInStockService $backInStockService,
    ) {}

    public function reservationMinutes(): int
    {
        return max(1, (int) config('shop.reservation_minutes', 15));
    }

    public function reservationExpiresAt(): Carbon
    {
        return now()->addMinutes($this->reservationMinutes());
    }

    public function physicalQuantity(ProductVariant $productVariant): int
    {
        $productVariant->loadMissing('stock');

        return $productVariant->stock?->quantity ?? 0;
    }

    public function reservedQuantity(ProductVariant $productVariant, ?int $excludeCartItemId = null): int
    {
        return (int) CartItem::query()
            ->where('product_variant_id', $productVariant->id)
            ->where('reserved_until', '>', now())
            ->when(
                $excludeCartItemId !== null,
                fn ($query) => $query->whereKeyNot($excludeCartItemId),
            )
            ->sum('quantity');
    }

    public function availableQuantity(ProductVariant $productVariant, ?int $excludeCartItemId = null): int
    {
        return max(
            0,
            $this->physicalQuantity($productVariant) - $this->reservedQuantity($productVariant, $excludeCartItemId),
        );
    }

    public function assertCanReserve(ProductVariant $productVariant, int $quantity, ?int $excludeCartItemId = null): void
    {
        if ($quantity <= 0) {
            throw new \RuntimeException('Geçersiz adet.');
        }

        if ($quantity > $this->availableQuantity($productVariant, $excludeCartItemId)) {
            throw new \RuntimeException('Yeterli stok bulunmamaktadır.');
        }
    }

    public function assertReservationIsActive(CartItem $cartItem): void
    {
        if ($cartItem->reserved_until === null || $cartItem->reserved_until->isPast()) {
            throw new \RuntimeException(
                'Sepetinizdeki rezervasyon süresi dolmuş ürünler var. Lütfen sepeti güncelleyin.'
            );
        }
    }

    public function decrementStock(ProductVariant $productVariant, int $quantity): void
    {
        $previousQuantity = $this->physicalQuantity($productVariant);

        Stock::query()
            ->where('product_variant_id', $productVariant->id)
            ->decrement('quantity', $quantity);

        $productVariant->unsetRelation('stock');
        $productVariant->load(['stock', 'product.vendor']);
        $this->lowStockService->evaluateVariant($productVariant, $previousQuantity);
        $this->backInStockService->evaluateVariant($productVariant, $previousQuantity);
    }

    public function incrementStock(ProductVariant $productVariant, int $quantity): void
    {
        $previousQuantity = $this->physicalQuantity($productVariant);

        Stock::query()
            ->where('product_variant_id', $productVariant->id)
            ->increment('quantity', $quantity);

        $productVariant->unsetRelation('stock');
        $productVariant->load(['stock', 'product.vendor']);
        $this->lowStockService->evaluateVariant($productVariant, $previousQuantity);
        $this->backInStockService->evaluateVariant($productVariant, $previousQuantity);
    }
}
