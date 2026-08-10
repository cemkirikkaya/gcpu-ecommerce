<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    public function __construct(
        private ProductRepository $repository,
        private ProductCatalogService $catalogService,
    ) {}

    public function getById(int $id): Product
    {
        return $this->repository->findById($id);
    }

    /**
     * @return Collection<int, Category>
     */
    public function getGroupedByCategory(): Collection
    {
        return $this->repository->getGroupedByCategory();
    }

    /**
     * @return Collection<int, Product>
     */
    public function getUncategorized(): Collection
    {
        return $this->repository->getUncategorized();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listFiltered(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateFiltered($filters);
    }

    public function create(array $data): Product
    {
        return $this->repository->create($data);
    }

    public function update(Product $product, array $data): Product
    {
        return $this->repository->update($product, $data);
    }

    public function delete(Product $product): void
    {
        $this->catalogService->deleteProduct($product);
    }
}
