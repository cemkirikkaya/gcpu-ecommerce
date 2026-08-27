<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SearchQuery;
use App\Services\ProductSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchAnalyticsController extends Controller
{
    public function __invoke(Request $request, ProductSearchService $productSearchService): JsonResponse
    {
        $this->authorize('viewAny', SearchQuery::class);

        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'days' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:365'],
        ]);

        return response()->json([
            'analytics' => $productSearchService->analytics(
                limit: $validated['limit'] ?? 20,
                days: array_key_exists('days', $validated) ? (int) $validated['days'] : null,
            ),
        ]);
    }
}
