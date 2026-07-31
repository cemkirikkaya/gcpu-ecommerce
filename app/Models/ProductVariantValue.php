<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariantValue extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_variant_id',
        'variant_value_id',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function variantValue(): BelongsTo
    {
        return $this->belongsTo(VariantValue::class);
    }
}
