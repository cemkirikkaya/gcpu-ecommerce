<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\ProductSearchSuggestRequest;
use App\Http\Resources\Api\ProductSearchSuggestionResource;
use App\Services\ProductSearchService;
use Illuminate\Http\JsonResponse;

class ProductSearchController extends Controller
{
    public function __construct(
        private ProductSearchService $productSearchService,
    ) {}

    public function suggest(ProductSearchSuggestRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $products = $this->productSearchService->suggest(
            $validated['q'],
            $validated['limit'] ?? 8,
        );

        return response()->json([
            'suggestions' => ProductSearchSuggestionResource::collection($products),
        ]);
    }

    public function popular(): JsonResponse
    {
        return response()->json([
            'popular' => $this->productSearchService->popular(),
        ]);
    }
}
