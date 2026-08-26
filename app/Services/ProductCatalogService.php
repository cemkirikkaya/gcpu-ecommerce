<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use App\Models\Stock;
use App\Models\User;
use App\Models\Variant;
use App\Models\VariantValue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductCatalogService
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array{product: Product, merged: bool}
     */
    public function storeOrMergeProduct(array $validated, User $owner): array
    {
        $catalogVariants = $validated['catalog_variants'];
        unset($validated['catalog_variants']);

        $validated['user_id'] = $owner->isVendor() ? $owner->id : null;

        $existing = $this->findByName($validated['name'], $owner);

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

    public function findByName(string $name, User $owner): ?Product
    {
        $normalized = Str::lower(trim($name));

        $query = Product::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalized]);

        if ($owner->isVendor()) {
            $query->where('user_id', $owner->id);
        } else {
            $query->whereNull('user_id');
        }

        return $query->first();
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
                    'Beden' => $row['size'] ?? null,
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

    public function storeCoverImage(Product $product, UploadedFile $file): Image
    {
        $extension = $file->guessExtension() ?? 'jpg';
        $filename = Str::slug($product->name).'-'.$product->id.'.'.$extension;
        $relativePath = 'catalog/products/'.$filename;

        $this->storePublicProductImage($file, $filename);

        Image::query()
            ->where('product_id', $product->id)
            ->where('is_cover', true)
            ->whereNull('product_variant_id')
            ->get()
            ->each(function (Image $image): void {
                $absolutePath = storage_path('app/public/'.$image->image);

                if (File::exists($absolutePath)) {
                    File::delete($absolutePath);
                }

                $image->delete();
            });

        return Image::query()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'image' => $relativePath,
            'label' => $product->name,
            'is_cover' => true,
            'sort_order' => 0,
        ]);
    }

    public function storeGalleryImage(Product $product, UploadedFile $file): Image
    {
        $extension = $file->guessExtension() ?? 'jpg';
        $filename = Str::slug($product->name).'-'.$product->id.'-'.Str::uuid().'.'.$extension;
        $relativePath = 'catalog/products/'.$filename;

        $this->storePublicProductImage($file, $filename);

        $hasCover = Image::query()
            ->where('product_id', $product->id)
            ->whereNull('product_variant_id')
            ->where('is_cover', true)
            ->exists();

        $maxSortOrder = Image::query()
            ->where('product_id', $product->id)
            ->whereNull('product_variant_id')
            ->max('sort_order');

        return Image::query()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'image' => $relativePath,
            'label' => $product->name,
            'is_cover' => ! $hasCover,
            'sort_order' => ($maxSortOrder ?? -1) + 1,
        ]);
    }

    public function deleteProductImage(Product $product, Image $image): void
    {
        if ($image->product_id !== $product->id || $image->product_variant_id !== null) {
            abort(404);
        }

        $absolutePath = storage_path('app/public/'.$image->image);

        if (File::exists($absolutePath)) {
            File::delete($absolutePath);
        }

        $wasCover = $image->is_cover;
        $image->delete();

        if (! $wasCover) {
            return;
        }

        $nextCover = Image::query()
            ->where('product_id', $product->id)
            ->whereNull('product_variant_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if ($nextCover === null) {
            return;
        }

        Image::query()
            ->where('product_id', $product->id)
            ->whereNull('product_variant_id')
            ->update(['is_cover' => false]);

        $nextCover->update([
            'is_cover' => true,
            'sort_order' => 0,
        ]);
    }

    public function setProductCoverImage(Product $product, Image $image): Image
    {
        if ($image->product_id !== $product->id || $image->product_variant_id !== null) {
            abort(404);
        }

        Image::query()
            ->where('product_id', $product->id)
            ->whereNull('product_variant_id')
            ->where('is_cover', true)
            ->update(['is_cover' => false]);

        $image->update([
            'is_cover' => true,
            'sort_order' => 0,
        ]);

        return $image->fresh() ?? $image;
    }

    private function storePublicProductImage(UploadedFile $file, string $filename): void
    {
        Storage::disk('public')->putFileAs('catalog/products', $file, $filename);
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
            'Beden' => 'size',
        ];

        return $product->variants->map(function (ProductVariant $variant) use ($standardMap): array {
            $row = [
                'sku' => $variant->sku,
                'stock' => $variant->stock?->quantity ?? 0,
                'color' => null,
                'memory' => null,
                'model' => null,
                'size' => null,
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
