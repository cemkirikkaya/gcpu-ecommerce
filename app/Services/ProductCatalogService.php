<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use App\Models\Stock;
use App\Models\Variant;
use App\Models\VariantValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductCatalogService
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array{product: Product, merged: bool}
     */
    public function storeOrMergeProduct(array $validated): array
    {
        $catalogVariants = $validated['catalog_variants'];
        unset($validated['catalog_variants']);

        $existing = $this->findByName($validated['name']);

        if ($existing !== null) {
            $existing->update([
                'price' => $validated['price'],
                'description' => $validated['description'] ?? $existing->description,
                'category_id' => $validated['category_id'] ?? $existing->category_id,
            ]);

            $this->syncVariants($existing, $catalogVariants, replace: false);
            $this->ensureColorGrouping($existing);

            return ['product' => $existing, 'merged' => true];
        }

        $product = Product::query()->create($validated);
        $this->syncVariants($product, $catalogVariants);
        $this->ensureColorGrouping($product);

        return ['product' => $product, 'merged' => false];
    }

    public function findByName(string $name): ?Product
    {
        $normalized = Str::lower(trim($name));

        return Product::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalized])
            ->first();
    }

    public function ensureColorGrouping(Product $product): void
    {
        if ($product->base_variant_id !== null) {
            return;
        }

        $product->loadMissing('variants.variantValues.variantValue.variant');

        $hasColor = $product->variants->contains(function (ProductVariant $variant): bool {
            return $variant->variantValues->contains(
                fn (ProductVariantValue $value): bool => $value->variantValue?->variant?->slug === 'renk',
            );
        });

        if (! $hasColor) {
            return;
        }

        $colorVariant = Variant::query()->firstOrCreate(
            ['slug' => 'renk'],
            ['name' => 'Renk'],
        );

        $product->update(['base_variant_id' => $colorVariant->id]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $catalogVariants
     */
    public function syncVariants(Product $product, array $catalogVariants, bool $replace = true): void
    {
        DB::transaction(function () use ($product, $catalogVariants, $replace): void {
            if ($replace) {
                $this->removeVariantsPermanently($product);
            }

            foreach ($catalogVariants as $row) {
                if (blank($row['sku'] ?? null)) {
                    continue;
                }

                $productVariant = ProductVariant::query()->create([
                    'product_id' => $product->id,
                    'sku' => $row['sku'],
                ]);

                Stock::query()->create([
                    'product_variant_id' => $productVariant->id,
                    'quantity' => (int) ($row['stock'] ?? 0),
                ]);

                $attributes = [
                    'Renk' => $row['color'] ?? null,
                    'Hafıza' => $row['memory'] ?? null,
                    'Model' => $row['model'] ?? null,
                ];

                foreach ($row['extra_attributes'] ?? [] as $extra) {
                    $name = trim((string) ($extra['name'] ?? ''));
                    $value = trim((string) ($extra['value'] ?? ''));

                    if ($name !== '' && $value !== '') {
                        $attributes[$name] = $value;
                    }
                }

                foreach ($attributes as $attributeName => $attributeValue) {
                    if (blank($attributeValue)) {
                        continue;
                    }

                    $this->attachAttribute($product, $productVariant, $attributeName, (string) $attributeValue);
                }

                if (! blank($row['image'] ?? null)) {
                    Image::query()->create([
                        'product_id' => $product->id,
                        'product_variant_id' => $productVariant->id,
                        'image' => $row['image'],
                        'label' => collect([$row['color'] ?? null, $row['model'] ?? null, $row['memory'] ?? null])
                            ->filter()
                            ->implode(' · '),
                        'is_cover' => (bool) ($row['is_cover'] ?? false),
                    ]);
                }
            }
        });
    }

    public function deleteProduct(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $this->removeVariantsPermanently($product);

            if (! $product->trashed()) {
                $product->delete();
            }
        });
    }

    private function removeVariantsPermanently(Product $product): void
    {
        ProductVariant::withTrashed()
            ->where('product_id', $product->id)
            ->each(function (ProductVariant $variant): void {
                Image::withTrashed()
                    ->where('product_variant_id', $variant->id)
                    ->forceDelete();

                ProductVariantValue::withTrashed()
                    ->where('product_variant_id', $variant->id)
                    ->forceDelete();

                Stock::withTrashed()
                    ->where('product_variant_id', $variant->id)
                    ->forceDelete();

                $variant->clearMediaCollection('variant-images');
                $variant->forceDelete();
            });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function catalogVariantsFromProduct(Product $product): array
    {
        $product->loadMissing([
            'variants.stock',
            'variants.images',
            'variants.variantValues.variantValue.variant',
        ]);

        $standardMap = [
            'Renk' => 'color',
            'Hafıza' => 'memory',
            'Model' => 'model',
        ];

        return $product->variants->map(function (ProductVariant $variant) use ($standardMap): array {
            $row = [
                'sku' => $variant->sku,
                'stock' => $variant->stock?->quantity ?? 0,
                'color' => null,
                'memory' => null,
                'model' => null,
                'image' => $variant->images->first()?->image,
                'is_cover' => $variant->images->first()?->is_cover ?? false,
                'extra_attributes' => [],
            ];

            foreach ($variant->variantValues as $productVariantValue) {
                $attributeName = $productVariantValue->variantValue?->variant?->name;
                $attributeValue = $productVariantValue->variantValue?->value;

                if ($attributeName === null || $attributeValue === null) {
                    continue;
                }

                if (isset($standardMap[$attributeName])) {
                    $row[$standardMap[$attributeName]] = $attributeValue;

                    continue;
                }

                $row['extra_attributes'][] = [
                    'name' => $attributeName,
                    'value' => $attributeValue,
                ];
            }

            return $row;
        })->values()->all();
    }

    public function attachAttribute(Product $product, ProductVariant $productVariant, string $attributeName, string $attributeValue): void
    {
        $variant = Variant::query()->firstOrCreate(
            ['slug' => Str::slug($attributeName)],
            ['name' => $attributeName],
        );

        if ($product->category_id !== null) {
            Category::query()
                ->find($product->category_id)
                ?->variants()
                ->syncWithoutDetaching([$variant->id]);
        }

        $variantValue = VariantValue::query()->firstOrCreate([
            'variant_id' => $variant->id,
            'value' => $attributeValue,
        ]);

        ProductVariantValue::query()->create([
            'product_variant_id' => $productVariant->id,
            'variant_value_id' => $variantValue->id,
        ]);
    }
}
