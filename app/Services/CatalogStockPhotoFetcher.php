<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CatalogStockPhotoFetcher
{
    public function __construct(
        private CatalogImageGenerator $fallbackGenerator,
    ) {}

    public function downloadProductCover(Product $product): string
    {
        $relativePath = 'catalog/products/'.Str::slug($product->name).'-'.$product->id.'.jpg';
        $absolutePath = storage_path('app/public/'.$relativePath);

        $this->ensureDirectory($absolutePath);

        if (! $this->downloadPhoto($product, $product->id, $absolutePath)) {
            return $this->fallbackGenerator->generateProductCover($product);
        }

        return $relativePath;
    }

    public function downloadVariantImage(Product $product, ProductVariant $variant): string
    {
        $relativePath = 'catalog/variants/'.Str::slug($variant->sku).'.jpg';
        $absolutePath = storage_path('app/public/'.$relativePath);

        $this->ensureDirectory($absolutePath);

        $colorAttribute = $variant->attributeList()
            ->first(fn (array $attribute): bool => in_array($attribute['name'], ['Renk', 'Color', 'Colour'], true));
        $colorLabel = $colorAttribute['value'] ?? 'Standart';

        if (! $this->downloadPhoto($product, $variant->id, $absolutePath)) {
            return $this->fallbackGenerator->generateVariantImage($product, $variant, $colorLabel);
        }

        return $relativePath;
    }

    private function downloadPhoto(Product $product, int $lock, string $absolutePath): bool
    {
        $attempts = [
            $this->resolveTags($product),
            config('catalog-photos.default', 'product,retail'),
        ];

        foreach (array_unique($attempts) as $tags) {
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $url = sprintf(
                    'https://loremflickr.com/800/1000/%s/all?lock=%d',
                    $tags,
                    $lock + $attempt,
                );

                try {
                    $response = Http::timeout(45)
                        ->withOptions(['allow_redirects' => true])
                        ->withHeaders([
                            'User-Agent' => 'EcommerceCatalogSeeder/1.0',
                        ])
                        ->get($url);

                    if ($response->successful() && $this->isValidImage($response->body())) {
                        $written = file_put_contents($absolutePath, $response->body());

                        if ($written !== false) {
                            return true;
                        }
                    }
                } catch (\Throwable) {
                    // Try next attempt or tag set.
                }

                usleep(250_000);
            }
        }

        return false;
    }

    private function resolveTags(Product $product): string
    {
        $product->loadMissing('category.parent');

        $categorySlug = $product->category?->slug;
        $parentSlug = $product->category?->parent?->slug;

        foreach ([$categorySlug, $parentSlug, 'default'] as $slug) {
            if ($slug === null) {
                continue;
            }

            $tags = config("catalog-photos.{$slug}");

            if (is_string($tags) && $tags !== '') {
                return $tags;
            }
        }

        return config('catalog-photos.default', 'product,retail');
    }

    private function isValidImage(string $body): bool
    {
        if (strlen($body) < 1024) {
            return false;
        }

        return str_starts_with($body, "\xFF\xD8\xFF");
    }

    private function ensureDirectory(string $absolutePath): void
    {
        $directory = dirname($absolutePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }
}
