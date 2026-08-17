<?php

namespace App\Services;

use App\Mail\LowStockMail;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class LowStockService
{
    public function threshold(): int
    {
        return max(0, (int) config('shop.low_stock_threshold', 5));
    }

    public function isLowStock(int $quantity): bool
    {
        return $quantity <= $this->threshold();
    }

    /**
     * @return list<array{
     *     product_id: int,
     *     product_name: string,
     *     variant_id: int,
     *     sku: string,
     *     quantity: int,
     * }>
     */
    public function alertsFor(User $user, int $limit = 10): array
    {
        return $this->lowStockVariantsFor($user)
            ->take($limit)
            ->map(fn (ProductVariant $variant): array => $this->alertPayloadFor($variant))
            ->values()
            ->all();
    }

    public function countFor(User $user): int
    {
        return $this->lowStockVariantsFor($user)->count();
    }

    public function evaluateVariant(ProductVariant $variant, ?int $previousQuantity = null): void
    {
        $variant->loadMissing(['stock', 'product.vendor']);

        $currentQuantity = $variant->stock?->quantity ?? 0;
        $previousQuantity ??= $currentQuantity;

        if ($previousQuantity > $this->threshold() && $this->isLowStock($currentQuantity)) {
            $this->notifyVendorIfNeeded($variant, $currentQuantity);

            return;
        }

        if ($currentQuantity > $this->threshold()) {
            $this->clearNotificationState($variant);
        }
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    private function lowStockVariantsFor(User $user): Collection
    {
        $productsQuery = Product::query()->with(['variants.stock']);

        if ($user->isVendor()) {
            $productsQuery->where('user_id', $user->id);
        }

        return $productsQuery
            ->get()
            ->flatMap(fn (Product $product) => $product->variants)
            ->filter(fn (ProductVariant $variant): bool => $this->isLowStock($variant->stock?->quantity ?? 0))
            ->sortBy(fn (ProductVariant $variant): int => $variant->stock?->quantity ?? 0)
            ->values();
    }

    /**
     * @return array{
     *     product_id: int,
     *     product_name: string,
     *     variant_id: int,
     *     sku: string,
     *     quantity: int,
     * }
     */
    private function alertPayloadFor(ProductVariant $variant): array
    {
        $variant->loadMissing('product');

        return [
            'product_id' => (int) $variant->product_id,
            'product_name' => $variant->product?->name ?? 'Ürün',
            'variant_id' => $variant->id,
            'sku' => $variant->sku,
            'quantity' => $variant->stock?->quantity ?? 0,
        ];
    }

    private function notifyVendorIfNeeded(ProductVariant $variant, int $currentQuantity): void
    {
        if (Cache::has($this->notificationCacheKey($variant))) {
            return;
        }

        $vendor = $variant->product?->vendor;

        if ($vendor === null || $vendor->email === null) {
            return;
        }

        Mail::to($vendor)->send(new LowStockMail($variant, $currentQuantity, $this->threshold()));

        Cache::put($this->notificationCacheKey($variant), true, now()->addDays(30));
    }

    private function clearNotificationState(ProductVariant $variant): void
    {
        Cache::forget($this->notificationCacheKey($variant));
    }

    private function notificationCacheKey(ProductVariant $variant): string
    {
        return "low_stock_notified:{$variant->id}";
    }
}
