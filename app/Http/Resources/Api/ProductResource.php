<?php

namespace App\Http\Resources\Api;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
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
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
            ]),
            'image_url' => $this->coverImageUrl(),
            'base_variant' => $this->whenLoaded('baseVariant', fn () => $this->baseVariant?->name),
            'variant_groups' => $this->when(
                $this->relationLoaded('variants'),
                fn () => $this->variantsGroupedByBaseVariant()
                    ->map(fn ($variants, $label) => [
                        'label' => $label,
                        'variants' => ProductVariantResource::collection($variants),
                    ])
                    ->values(),
            ),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
        ];
    }
}
