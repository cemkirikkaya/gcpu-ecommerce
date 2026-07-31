<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $service
    ) {}

    public function index(): View
    {
        $categories = $this->service->getGroupedByCategory();
        $uncategorizedProducts = $this->service->getUncategorized();

        return view('products.index', compact('categories', 'uncategorizedProducts'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->service->create(
            $request->validated()
        );

        return back();
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->service->update(
            $product,
            $request->validated()
        );

        return back();
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->service->delete($product);

        return back();
    }
}
