<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    /**
     * @param  array{
     *     search?: string|null,
     *     category?: string|null,
     *     min_price?: float|null,
     *     max_price?: float|null,
     *     sort?: string|null,
     *     per_page?: int|null,
     * }  $filters
     */
    public function paginateFiltered(array $filters): LengthAwarePaginator
    {
        $query = Product::query()->tap(fn ($builder) => $this->productWithRelations($builder));

        if (! empty($filters['search'])) {
            $search = mb_strtolower($filters['search']);
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.$search.'%']);
        }

        if (! empty($filters['category'])) {
            $category = $filters['category'];
            $query->whereHas('category', function ($categoryQuery) use ($category): void {
                $categoryQuery->where('slug', $category)
                    ->orWhere('id', is_numeric($category) ? (int) $category : 0);
            });
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        $sort = $filters['sort'] ?? 'latest';

        match ($sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'name_asc' => $query->orderBy('name'),
            default => $query->latest(),
        };

        return $query->paginate($filters['per_page'] ?? 12);
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
