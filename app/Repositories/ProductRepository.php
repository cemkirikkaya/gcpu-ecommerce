<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository
{
    /**
     * @return Collection<int, Category>
     */
    public function getGroupedByCategory(): Collection
    {
        return Category::query()
            ->with(['parent'])
            ->whereNull('parent_id')
            ->where(function ($query): void {
                $query->whereHas('products')
                    ->orWhereHas('children.products');
            })
            ->with(['children' => function ($query): void {
                $query->whereHas('products')
                    ->with(['products' => fn ($productQuery) => $this->productWithRelations($productQuery)])
                    ->orderBy('name');
            }])
            ->orderBy('name')
            ->get()
            ->map(function (Category $category): Category {
                $directProducts = Product::query()
                    ->where('category_id', $category->id)
                    ->tap(fn ($query) => $this->productWithRelations($query))
                    ->latest()
                    ->get();

                $category->setRelation('directProducts', $directProducts);

                return $category;
            });
    }

    /**
     * @return Collection<int, Product>
     */
    public function getUncategorized(): Collection
    {
        return Product::query()
            ->whereNull('category_id')
            ->tap(fn ($query) => $this->productWithRelations($query))
            ->latest()
            ->get();
    }

    private function productWithRelations($query): void
    {
        $query->with([
            'baseVariant',
            'category.parent',
            'variants.stock',
            'variants.images',
            'variants.product',
            'variants.variantValues.variantValue.variant',
            'images',
            'media',
        ]);
    }

    public function findById(int $id): Product
    {
        return Product::query()->findOrFail($id);
    }

    public function create(array $data): Product
    {
        return Product::query()->create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
