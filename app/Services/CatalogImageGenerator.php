<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Str;

class CatalogImageGenerator
{
    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function categoryPalette(?string $categorySlug): array
    {
        return match ($categorySlug) {
            'telefonlar', 'tabletler', 'akilli-saatler', 'kulakliklar', 'elektronik' => [30, 58, 95],
            'tisortler', 'pantolonlar', 'ceketler', 'giyim' => [120, 72, 48],
            'cantalar', 'cuzdanlar', 'gozlukler', 'aksesuar' => [88, 64, 42],
            'mutfak', 'dekorasyon', 'kisisel-bakim', 'ev-yasam' => [72, 98, 88],
            default => [90, 90, 90],
        };
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function colorTint(string $colorName, array $base): array
    {
        $normalized = Str::lower($colorName);

        $tint = match (true) {
            str_contains($normalized, 'siyah') => [-40, -40, -40],
            str_contains($normalized, 'beyaz') => [55, 55, 55],
            str_contains($normalized, 'mavi') || str_contains($normalized, 'lacivert') => [-20, -10, 35],
            str_contains($normalized, 'yeşil') || str_contains($normalized, 'yesil') || str_contains($normalized, 'haki') => [-15, 25, -10],
            str_contains($normalized, 'kırmızı') || str_contains($normalized, 'kirmizi') || str_contains($normalized, 'pembe') => [35, -20, -20],
            str_contains($normalized, 'gri') || str_contains($normalized, 'gümüş') || str_contains($normalized, 'gumus') => [10, 10, 10],
            str_contains($normalized, 'bej') || str_contains($normalized, 'krem') => [25, 18, 8],
            str_contains($normalized, 'kahverengi') => [20, 5, -25],
            str_contains($normalized, 'mor') => [15, -10, 30],
            str_contains($normalized, 'turuncu') => [35, 10, -25],
            str_contains($normalized, 'sarı') || str_contains($normalized, 'sari') || str_contains($normalized, 'altın') || str_contains($normalized, 'altin') => [30, 25, -30],
            default => [0, 0, 0],
        };

        return [
            max(0, min(255, $base[0] + $tint[0])),
            max(0, min(255, $base[1] + $tint[1])),
            max(0, min(255, $base[2] + $tint[2])),
        ];
    }

    public function generateProductCover(Product $product): string
    {
        $relativePath = 'catalog/products/'.Str::slug($product->name).'-'.$product->id.'.jpg';
        $absolutePath = storage_path('app/public/'.$relativePath);

        if (file_exists($absolutePath)) {
            return $relativePath;
        }

        $this->ensureDirectory($absolutePath);

        $palette = $this->categoryPalette($product->category?->slug);
        $this->writeImage($absolutePath, 800, 1000, $palette, $product->name, $product->category?->name);

        return $relativePath;
    }

    public function generateVariantImage(Product $product, ProductVariant $variant, string $colorLabel): string
    {
        $relativePath = 'catalog/variants/'.Str::slug($variant->sku).'.jpg';
        $absolutePath = storage_path('app/public/'.$relativePath);

        if (file_exists($absolutePath)) {
            return $relativePath;
        }

        $this->ensureDirectory($absolutePath);

        $palette = $this->colorTint(
            $colorLabel,
            $this->categoryPalette($product->category?->slug),
        );

        $subtitle = collect($variant->attributeList())
            ->map(fn (array $attribute): string => $attribute['value'])
            ->implode(' · ');

        $this->writeImage(
            $absolutePath,
            800,
            1000,
            $palette,
            $product->name,
            $subtitle !== '' ? $subtitle : $variant->sku,
        );

        return $relativePath;
    }

    private function ensureDirectory(string $absolutePath): void
    {
        $directory = dirname($absolutePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function writeImage(
        string $absolutePath,
        int $width,
        int $height,
        array $rgb,
        string $title,
        ?string $subtitle,
    ): void {
        if (! function_exists('imagecreatetruecolor')) {
            throw new \RuntimeException('GD extension is required to generate catalog images.');
        }

        $image = imagecreatetruecolor($width, $height);

        [$red, $green, $blue] = $rgb;

        for ($y = 0; $y < $height; $y++) {
            $factor = 1 - ($y / $height) * 0.35;
            $lineColor = imagecolorallocate(
                $image,
                (int) ($red * $factor),
                (int) ($green * $factor),
                (int) ($blue * $factor),
            );

            imageline($image, 0, $y, $width, $y, $lineColor);
        }

        $accent = imagecolorallocate($image, 255, 255, 255);
        $muted = imagecolorallocatealpha($image, 255, 255, 255, 40);
        imagefilledellipse($image, (int) ($width * 0.78), (int) ($height * 0.18), 220, 220, $muted);
        imagefilledellipse($image, (int) ($width * 0.18), (int) ($height * 0.72), 280, 280, $muted);

        $titleColor = imagecolorallocate($image, 250, 248, 245);
        $subtitleColor = imagecolorallocate($image, 230, 225, 218);

        $this->drawCenteredText($image, 5, $titleColor, $width / 2, $height * 0.42, $this->wrapText($title, 18));

        if ($subtitle !== null && $subtitle !== '') {
            $this->drawCenteredText($image, 3, $subtitleColor, $width / 2, $height * 0.58, $this->wrapText($subtitle, 28));
        }

        imagejpeg($image, $absolutePath, 88);
        imagedestroy($image);
    }

    /**
     * @return list<string>
     */
    private function wrapText(string $text, int $maxChars): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;

            if (mb_strlen($candidate) > $maxChars && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return array_slice($lines, 0, 3);
    }

    /**
     * @param  list<string>  $lines
     */
    private function drawCenteredText(
        \GdImage $image,
        int $fontSize,
        int $color,
        float $centerX,
        float $startY,
        array $lines,
    ): void {
        $lineHeight = $fontSize * 2.4;

        foreach ($lines as $index => $line) {
            $box = imagettfbbox($fontSize, 0, $this->fontPath(), $line);
            $textWidth = abs($box[2] - $box[0]);
            $x = (int) ($centerX - ($textWidth / 2));
            $y = (int) ($startY + ($index * $lineHeight));

            imagettftext($image, $fontSize, 0, $x, $y, $color, $this->fontPath(), $line);
        }
    }

    private function fontPath(): string
    {
        $candidates = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
            '/Library/Fonts/Arial Bold.ttf',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        throw new \RuntimeException('No TrueType font found for catalog image generation.');
    }
}
