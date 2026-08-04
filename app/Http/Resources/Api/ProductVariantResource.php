<?php

namespace App\Http\Resources\Api;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductVariant */
class ProductVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $image = $this->images->first();

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'label' => $this->displayLabel(),
            'attributes' => $this->attributeList(),
            'price' => $this->whenLoaded('product', fn () => (float) $this->product->price),
            'available_quantity' => $this->availableQuantity(),
            'image_url' => $image !== null ? '/storage/'.$image->image : null,
        ];
    }
}
