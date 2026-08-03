<?php

namespace App\Models;

use App\Services\StockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 */
class ProductVariant extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'sku',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('variant-images');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variantValues(): HasMany
    {
        return $this->hasMany(ProductVariantValue::class);
    }

    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    public function stockQuantity(): int
    {
        return $this->stock?->quantity ?? 0;
    }

    public function availableQuantity(?int $excludeCartItemId = null): int
    {
        return app(StockService::class)->availableQuantity($this, $excludeCartItemId);
    }

    public function reservedQuantity(?int $excludeCartItemId = null): int
    {
        return app(StockService::class)->reservedQuantity($this, $excludeCartItemId);
    }

    /**
     * @return Collection<int, array{name: string, value: string}>
     */
    public function attributeList(): Collection
    {
        $this->loadMissing('variantValues.variantValue.variant');

        return $this->variantValues
            ->map(function (ProductVariantValue $productVariantValue): ?array {
                $name = $productVariantValue->variantValue?->variant?->name;
                $value = $productVariantValue->variantValue?->value;

                if ($name === null || $value === null) {
                    return null;
                }

                return [
                    'name' => $name,
                    'value' => $value,
                ];
            })
            ->filter()
            ->values();
    }

    public function displayLabel(): string
    {
        $attributes = $this->attributeList()
            ->map(fn (array $attribute): string => "{$attribute['name']}: {$attribute['value']}")
            ->implode(' · ');

        if ($attributes === '') {
            return $this->sku;
        }

        return $attributes;
    }
}
