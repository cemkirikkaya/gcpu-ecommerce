<?php

namespace App\Http\Resources\Api;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class AdminProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'image_url' => $this->coverImageUrl(),
            'images' => $this->when(
                $this->relationLoaded('images'),
                fn () => ProductImageResource::collection($this->galleryImages()),
            ),
            'vendor_email' => $this->whenLoaded('vendor', fn () => $this->vendor?->email),
            'variants' => $this->whenLoaded('variants', fn () => $this->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'label' => $variant->displayLabel(),
                'stock_id' => $variant->stock?->id,
                'quantity' => $variant->stock?->quantity ?? 0,
                'available_quantity' => $variant->availableQuantity(),
                'color' => $variant->variantValues
                    ->first(fn ($v) => $v->variantValue?->variant?->name === 'Renk')
                    ?->variantValue?->value,
                'memory' => $variant->variantValues
                    ->first(fn ($v) => $v->variantValue?->variant?->name === 'Hafıza')
                    ?->variantValue?->value,
                'model' => $variant->variantValues
                    ->first(fn ($v) => $v->variantValue?->variant?->name === 'Model')
                    ?->variantValue?->value,
                'size' => $variant->variantValues
                    ->first(fn ($v) => $v->variantValue?->variant?->name === 'Beden')
                    ?->variantValue?->value,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
