<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 */
class Product extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'base_variant_id',
        'name',
        'price',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product-images');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function baseVariant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'base_variant_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    public function coverImageUrl(): ?string
    {
        $mediaUrl = $this->getFirstMediaUrl('product-images');

        if ($mediaUrl !== '') {
            return $mediaUrl;
        }

        $this->loadMissing('images');

        $cover = $this->images
            ->firstWhere('is_cover', true)
            ?? $this->images->firstWhere('product_variant_id', null)
            ?? $this->images->first();

        if ($cover === null) {
            return null;
        }

        return '/storage/'.$cover->image;
    }

    /**
     * @return Collection<string, Collection<int, ProductVariant>>
     */
    public function variantsGroupedByBaseVariant(): Collection
    {
        $this->loadMissing([
            'baseVariant',
            'variants.stock',
            'variants.variantValues.variantValue.variant',
            'variants.images',
        ]);

        if ($this->base_variant_id === null) {
            return collect(['Tüm Seçenekler' => $this->variants]);
        }

        return $this->variants->groupBy(function (ProductVariant $variant): string {
            $baseAttribute = $variant->variantValues
                ->first(fn (ProductVariantValue $value): bool => $value->variantValue?->variant_id === $this->base_variant_id);

            return $baseAttribute?->variantValue?->value ?? 'Diğer';
        });
    }
}
