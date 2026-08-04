<?php

namespace Database\Seeders;

use App\Models\Image;
use App\Models\Product;
use App\Services\CatalogStockPhotoFetcher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ProductImageSeeder extends Seeder
{
    public function run(): void
    {
        $fetcher = app(CatalogStockPhotoFetcher::class);

        Product::query()
            ->with([
                'category.parent',
                'images',
                'variants.images',
                'variants.variantValues.variantValue.variant',
            ])
            ->orderBy('id')
            ->each(function (Product $product) use ($fetcher): void {
                $this->seedProductCover($product, $fetcher);
                usleep(200_000);
                $this->seedVariantImages($product, $fetcher);
            });

        $this->command?->info('ProductImageSeeder tamamlandı.');
    }

    private function seedProductCover(
        Product $product,
        CatalogStockPhotoFetcher $fetcher,
    ): void {
        $existingCover = Image::query()
            ->where('product_id', $product->id)
            ->where('is_cover', true)
            ->whereNull('product_variant_id')
            ->first();

        if ($existingCover !== null) {
            $this->deleteImageFile($existingCover->image);
            $existingCover->delete();
        }

        $path = $fetcher->downloadProductCover($product);

        Image::query()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'image' => $path,
            'label' => $product->name,
            'is_cover' => true,
            'sort_order' => 0,
        ]);

        $this->command?->line("Kapak güncellendi: {$product->name}");
    }

    private function seedVariantImages(
        Product $product,
        CatalogStockPhotoFetcher $fetcher,
    ): void {
        foreach ($product->variants as $variant) {
            if ($variant->images()->exists()) {
                continue;
            }

            $path = $fetcher->downloadVariantImage($product, $variant);

            Image::query()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'image' => $path,
                'label' => $variant->displayLabel(),
                'is_cover' => false,
                'sort_order' => 0,
            ]);
        }
    }

    private function deleteImageFile(string $relativePath): void
    {
        $absolutePath = storage_path('app/public/'.$relativePath);

        if (File::exists($absolutePath)) {
            File::delete($absolutePath);
        }
    }
}
