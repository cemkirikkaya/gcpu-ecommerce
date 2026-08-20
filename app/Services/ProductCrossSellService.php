<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductCrossSellService
{
    public function __construct(
        private ProductRepository $productRepository,
    ) {}

    /**
     * @return Collection<int, Product>
     */
    public function forProduct(Product $product, int $limit = 4): Collection
    {
        $productIds = DB::table('order_items as source_items')
            ->join('cart_items as source_cart_items', 'source_items.cart_item_id', '=', 'source_cart_items.id')
            ->join('product_variants as source_variants', 'source_cart_items.product_variant_id', '=', 'source_variants.id')
            ->join('orders', 'source_items.order_id', '=', 'orders.id')
            ->join('order_items as co_items', function ($join): void {
                $join->on('co_items.order_id', '=', 'source_items.order_id')
                    ->whereNull('co_items.deleted_at');
            })
            ->join('cart_items as co_cart_items', 'co_items.cart_item_id', '=', 'co_cart_items.id')
            ->join('product_variants as co_variants', 'co_cart_items.product_variant_id', '=', 'co_variants.id')
            ->join('products as co_products', 'co_variants.product_id', '=', 'co_products.id')
            ->where('source_variants.product_id', $product->id)
            ->where('co_variants.product_id', '!=', $product->id)
            ->where('orders.payment_status', PaymentStatus::Paid->value)
            ->whereNull('source_items.deleted_at')
            ->whereNull('co_products.deleted_at')
            ->groupBy('co_variants.product_id')
            ->orderByDesc('co_occurrence_count')
            ->limit($limit)
            ->select([
                'co_variants.product_id',
                DB::raw('COUNT(*) as co_occurrence_count'),
            ])
            ->pluck('product_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($productIds === []) {
            return new Collection;
        }

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->tap(fn ($query) => $this->productRepository->loadForListing($query))
            ->get();

        return $products
            ->sortBy(fn (Product $listedProduct): int => array_search($listedProduct->id, $productIds, true))
            ->values();
    }
}
