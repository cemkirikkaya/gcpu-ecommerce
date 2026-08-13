<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(
            Variant::class,
            'category_variant'
        );
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return list<int>
     */
    public static function idsInSubtree(int $rootId): array
    {
        $categories = static::query()->get(['id', 'parent_id']);
        $ids = collect([$rootId]);

        while (true) {
            $childIds = $categories
                ->whereIn('parent_id', $ids)
                ->pluck('id');

            if ($childIds->isEmpty()) {
                break;
            }

            $ids = $ids->merge($childIds);
        }

        return $ids->unique()->values()->all();
    }
}
