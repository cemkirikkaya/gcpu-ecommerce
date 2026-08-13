<?php

namespace App\Http\Resources\Api;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Category */
class CategoryDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'products_count' => $this->products_count_in_subtree ?? $this->products_count ?? 0,
            'parent' => $this->whenLoaded('parent', function (): ?array {
                if ($this->parent === null) {
                    return null;
                }

                return [
                    'id' => $this->parent->id,
                    'name' => $this->parent->name,
                    'slug' => $this->parent->slug,
                ];
            }),
            'children' => $this->whenLoaded('children', function () {
                return $this->children->map(fn (Category $child): array => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'description' => $child->description,
                    'products_count' => $child->products_count ?? 0,
                ])->values();
            }),
        ];
    }
}
