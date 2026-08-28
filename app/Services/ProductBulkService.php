<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ProductBulkService
{
    /**
     * @var array<string, list<string>>
     */
    private const IMPORT_HEADER_ALIASES = [
        'name' => ['name', 'urun', 'urun_adi', 'product', 'product_name'],
        'description' => ['description', 'aciklama', 'desc'],
        'price' => ['price', 'fiyat'],
        'category' => ['category', 'kategori', 'category_slug', 'category_name'],
        'sku' => ['sku', 'stok_kodu', 'stock_code'],
        'stock' => ['stock', 'stok', 'quantity', 'qty'],
        'color' => ['color', 'renk'],
        'memory' => ['memory', 'hafiza', 'hafıza'],
        'model' => ['model'],
        'size' => ['size', 'beden'],
    ];

    /**
     * @var array<string, list<string>>
     */
    private const UPDATE_HEADER_ALIASES = [
        'sku' => ['sku', 'stok_kodu', 'stock_code'],
        'price' => ['price', 'fiyat'],
        'stock' => ['stock', 'stok', 'quantity', 'qty'],
    ];

    public function __construct(
        private ProductCatalogService $catalogService,
        private LowStockService $lowStockService,
        private BackInStockService $backInStockService,
    ) {}

    /**
     * @return array{created: int, merged: int, skipped: int, errors: list<array{row: int, message: string}>}
     */
    public function importFromCsv(UploadedFile $file, User $user): array
    {
        $parsed = $this->parseCsv($file);
        $rows = $this->mapRows($parsed['headers'], $parsed['rows'], self::IMPORT_HEADER_ALIASES);

        if (! in_array('name', $parsed['headers'], true) || ! in_array('sku', $parsed['headers'], true)) {
            throw new InvalidArgumentException('CSV dosyasında name ve sku sütunları zorunludur.');
        }

        $result = [
            'created' => 0,
            'merged' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        /** @var array<string, list<array{row: int, data: array<string, string|null>}>> $grouped */
        $grouped = [];

        foreach ($rows as $row) {
            $name = trim((string) ($row['data']['name'] ?? ''));

            if ($name === '') {
                $result['errors'][] = [
                    'row' => $row['row'],
                    'message' => 'Ürün adı boş olamaz.',
                ];

                continue;
            }

            $normalizedName = Str::lower(trim($name));
            $grouped[$normalizedName][] = $row;
        }

        foreach ($grouped as $productRows) {
            $firstRow = $productRows[0];
            $catalogVariants = [];
            $hasVariantError = false;

            foreach ($productRows as $productRow) {
                $variantRow = $this->buildImportVariant($productRow);

                if ($variantRow === null) {
                    $result['errors'][] = [
                        'row' => $productRow['row'],
                        'message' => 'SKU ve stok alanları zorunludur.',
                    ];
                    $hasVariantError = true;

                    continue;
                }

                if ($this->skuExistsOnDifferentProduct($variantRow['sku'], trim((string) ($firstRow['data']['name'] ?? '')), $user)) {
                    $result['errors'][] = [
                        'row' => $productRow['row'],
                        'message' => "SKU {$variantRow['sku']} başka bir üründe kullanılıyor.",
                    ];
                    $hasVariantError = true;

                    continue;
                }

                if (ProductVariant::query()->where('sku', $variantRow['sku'])->exists()) {
                    $result['skipped']++;

                    continue;
                }

                $catalogVariants[] = $variantRow;
            }

            if ($hasVariantError || $catalogVariants === []) {
                if ($catalogVariants === [] && ! $hasVariantError) {
                    $result['skipped'] += count($productRows);
                }

                continue;
            }

            $firstData = $firstRow['data'];
            $price = $this->parseDecimal($firstData['price'] ?? null);

            if ($price === null) {
                $result['errors'][] = [
                    'row' => $firstRow['row'],
                    'message' => 'Geçerli bir fiyat girin.',
                ];

                continue;
            }

            try {
                $storeResult = $this->catalogService->storeOrMergeProduct([
                    'name' => trim((string) $firstData['name']),
                    'description' => blank($firstData['description'] ?? null) ? null : trim((string) $firstData['description']),
                    'price' => $price,
                    'category_id' => $this->resolveCategoryId($firstData['category'] ?? null),
                    'catalog_variants' => $catalogVariants,
                ], $user);

                if ($storeResult['merged']) {
                    $result['merged']++;
                } else {
                    $result['created']++;
                }
            } catch (\Throwable $exception) {
                $result['errors'][] = [
                    'row' => $firstRow['row'],
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return $result;
    }

    /**
     * @return array{updated: int, skipped: int, errors: list<array{row: int, message: string}>}
     */
    public function updateFromCsv(UploadedFile $file, User $user): array
    {
        $parsed = $this->parseCsv($file);
        $rows = $this->mapRows($parsed['headers'], $parsed['rows'], self::UPDATE_HEADER_ALIASES);

        if (! in_array('sku', $parsed['headers'], true)) {
            throw new InvalidArgumentException('CSV dosyasında sku sütunu zorunludur.');
        }

        $result = [
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($rows as $row) {
            $sku = trim((string) ($row['data']['sku'] ?? ''));

            if ($sku === '') {
                $result['errors'][] = [
                    'row' => $row['row'],
                    'message' => 'SKU boş olamaz.',
                ];

                continue;
            }

            $price = array_key_exists('price', $row['data']) && filled($row['data']['price'])
                ? $this->parseDecimal($row['data']['price'])
                : null;
            $stock = array_key_exists('stock', $row['data']) && filled($row['data']['stock'])
                ? $this->parseInteger($row['data']['stock'])
                : null;

            if ($price === null && $stock === null) {
                $result['errors'][] = [
                    'row' => $row['row'],
                    'message' => 'Fiyat veya stok alanlarından en az biri doldurulmalıdır.',
                ];

                continue;
            }

            if ($price !== null && $price < 0) {
                $result['errors'][] = [
                    'row' => $row['row'],
                    'message' => 'Fiyat negatif olamaz.',
                ];

                continue;
            }

            if ($stock !== null && $stock < 0) {
                $result['errors'][] = [
                    'row' => $row['row'],
                    'message' => 'Stok negatif olamaz.',
                ];

                continue;
            }

            $variant = ProductVariant::query()
                ->with(['stock', 'product'])
                ->where('sku', $sku)
                ->first();

            if ($variant === null || $variant->product === null) {
                $result['errors'][] = [
                    'row' => $row['row'],
                    'message' => "SKU {$sku} bulunamadı.",
                ];

                continue;
            }

            if (! $this->userCanManageProduct($user, $variant->product)) {
                $result['errors'][] = [
                    'row' => $row['row'],
                    'message' => "SKU {$sku} için güncelleme yetkiniz yok.",
                ];

                continue;
            }

            $updated = false;

            DB::transaction(function () use ($variant, $price, $stock, &$updated): void {
                if ($price !== null) {
                    $variant->product?->update(['price' => $price]);
                    $updated = true;
                }

                if ($stock !== null && $variant->stock !== null) {
                    $previousQuantity = $variant->stock->quantity;
                    $variant->stock->update(['quantity' => $stock]);
                    $variant->stock->refresh();
                    $variant->unsetRelation('stock');
                    $variant->load('stock', 'product.vendor');
                    $this->lowStockService->evaluateVariant($variant, $previousQuantity);
                    $this->backInStockService->evaluateVariant($variant, $previousQuantity);
                    $updated = true;
                }
            });

            if ($updated) {
                $result['updated']++;
            } else {
                $result['skipped']++;
            }
        }

        return $result;
    }

    public function importTemplate(): string
    {
        return implode("\n", [
            'name,description,price,category,sku,stock,color,memory,model,size',
            'Bluetooth Kulaklık,Kablosuz kulaklık,1299.90,elektronik,KUL-BT-001,25,Siyah,,,',
            'Bluetooth Kulaklık,Kablosuz kulaklık,1299.90,elektronik,KUL-BT-002,18,Beyaz,,,',
        ]);
    }

    public function updateTemplate(): string
    {
        return implode("\n", [
            'sku,price,stock',
            'KUL-BT-001,1199.90,30',
            'KUL-BT-002,,12',
        ]);
    }

    /**
     * @return array{headers: list<string>, rows: list<list<string>>}
     */
    private function parseCsv(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        if ($path === false) {
            throw new InvalidArgumentException('CSV dosyası okunamadı.');
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new InvalidArgumentException('CSV dosyası okunamadı.');
        }

        $firstLine = fgets($handle);

        if ($firstLine === false) {
            fclose($handle);

            throw new InvalidArgumentException('CSV dosyası boş.');
        }

        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);

        $headerRow = fgetcsv($handle, 0, $delimiter);

        if ($headerRow === false || $headerRow === [null]) {
            fclose($handle);

            throw new InvalidArgumentException('CSV başlık satırı okunamadı.');
        }

        $headers = $this->resolveHeaders($headerRow);
        $rows = [];

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($this->isEmptyRow($data)) {
                continue;
            }

            $rows[] = $data;
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * @param  list<string>  $headerRow
     * @return list<string>
     */
    private function resolveHeaders(array $headerRow): array
    {
        $headers = [];

        foreach ($headerRow as $header) {
            $normalized = $this->normalizeHeader((string) $header);
            $headers[] = $this->mapHeader($normalized, self::IMPORT_HEADER_ALIASES)
                ?? $this->mapHeader($normalized, self::UPDATE_HEADER_ALIASES)
                ?? $normalized;
        }

        return $headers;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     * @param  array<string, list<string>>  $aliases
     * @return list<array{row: int, data: array<string, string|null>}>
     */
    private function mapRows(array $headers, array $rows, array $aliases): array
    {
        $mapped = [];

        foreach ($rows as $index => $row) {
            $data = [];

            foreach ($headers as $columnIndex => $header) {
                $data[$header] = isset($row[$columnIndex]) ? trim((string) $row[$columnIndex]) : null;
            }

            $mapped[] = [
                'row' => $index + 2,
                'data' => $data,
            ];
        }

        return $mapped;
    }

    /**
     * @param  array{row: int, data: array<string, string|null>}  $row
     * @return array{sku: string, stock: int, color?: string, memory?: string, model?: string, size?: string}|null
     */
    private function buildImportVariant(array $row): ?array
    {
        $sku = trim((string) ($row['data']['sku'] ?? ''));
        $stock = $this->parseInteger($row['data']['stock'] ?? null);

        if ($sku === '' || $stock === null) {
            return null;
        }

        $variant = [
            'sku' => $sku,
            'stock' => $stock,
        ];

        foreach (['color', 'memory', 'model', 'size'] as $attribute) {
            if (filled($row['data'][$attribute] ?? null)) {
                $variant[$attribute] = trim((string) $row['data'][$attribute]);
            }
        }

        return $variant;
    }

    private function skuExistsOnDifferentProduct(string $sku, string $productName, User $user): bool
    {
        $existingVariant = ProductVariant::query()
            ->with('product')
            ->where('sku', $sku)
            ->first();

        if ($existingVariant === null || $existingVariant->product === null) {
            return false;
        }

        $existingProduct = $existingVariant->product;
        $sameName = Str::lower(trim($existingProduct->name)) === Str::lower(trim($productName));

        if (! $sameName) {
            return true;
        }

        if ($user->isVendor()) {
            return $existingProduct->user_id !== $user->id;
        }

        return $existingProduct->user_id !== null;
    }

    private function resolveCategoryId(?string $category): ?int
    {
        if (blank($category)) {
            return null;
        }

        $categoryValue = trim((string) $category);

        return Category::query()
            ->where('slug', Str::slug($categoryValue))
            ->orWhere('name', $categoryValue)
            ->value('id');
    }

    private function userCanManageProduct(User $user, Product $product): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isVendor() && $product->user_id === $user->id;
    }

    private function normalizeHeader(string $header): string
    {
        $header = trim(str_replace("\xEF\xBB\xBF", '', $header));
        $header = mb_strtolower($header, 'UTF-8');
        $header = Str::ascii($header);
        $header = str_replace([' ', '-'], '_', $header);

        return $header;
    }

    /**
     * @param  array<string, list<string>>  $aliases
     */
    private function mapHeader(string $header, array $aliases): ?string
    {
        foreach ($aliases as $canonical => $options) {
            if (in_array($header, $options, true)) {
                return $canonical;
            }
        }

        return null;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function parseDecimal(?string $value): ?float
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = str_replace([' ', '₺', 'TL', 'tl'], '', trim($value));
        $normalized = str_replace(',', '.', $normalized);

        if (! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    private function parseInteger(?string $value): ?int
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = str_replace([' ', ','], '', trim($value));

        if (! is_numeric($normalized)) {
            return null;
        }

        return (int) $normalized;
    }
}
