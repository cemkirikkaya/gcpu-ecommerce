<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductReview\StoreProductReviewRequest;
use App\Http\Requests\ProductReview\UpdateProductReviewRequest;
use App\Http\Resources\Api\ProductReviewResource;
use App\Models\Product;
use App\Models\ProductReview;
use App\Services\ProductReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function __construct(private ProductReviewService $productReviewService) {}

    public function index(Product $product): JsonResponse
    {
        $this->authorize('viewAny', ProductReview::class);

        $reviews = ProductReview::query()
            ->where('product_id', $product->id)
            ->with('user:id,name')
            ->latest()
            ->paginate(10);

        $summary = ProductReview::query()
            ->where('product_id', $product->id)
            ->selectRaw('COUNT(*) as count, AVG(rating) as average')
            ->first();

        return response()->json([
            'reviews' => ProductReviewResource::collection($reviews->items()),
            'summary' => [
                'average' => $summary?->average !== null ? round((float) $summary->average, 1) : 0,
                'count' => (int) ($summary?->count ?? 0),
            ],
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    public function store(StoreProductReviewRequest $request, Product $product): JsonResponse
    {
        $this->authorize('create', ProductReview::class);

        $user = $request->user();

        if (! $this->productReviewService->canReview($user, $product)) {
            return response()->json([
                'message' => 'Yorum yazmak için bu ürünü satın alıp teslim almış olmanız gerekir.',
            ], 422);
        }

        if (ProductReview::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->exists()) {
            return response()->json([
                'message' => 'Bu ürün için zaten yorum yaptınız.',
            ], 422);
        }

        $review = ProductReview::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => $request->integer('rating'),
            'comment' => $request->string('comment')->toString(),
            'is_verified_purchase' => true,
        ]);

        $review->load('user:id,name');

        return response()->json([
            'review' => new ProductReviewResource($review),
            'message' => 'Yorumunuz kaydedildi.',
        ], 201);
    }

    public function update(
        UpdateProductReviewRequest $request,
        Product $product,
        ProductReview $review,
    ): JsonResponse {
        abort_unless($review->product_id === $product->id, 404);

        $this->authorize('update', $review);

        $review->update([
            'rating' => $request->integer('rating'),
            'comment' => $request->string('comment')->toString(),
        ]);

        $review->load('user:id,name');

        return response()->json([
            'review' => new ProductReviewResource($review),
            'message' => 'Yorumunuz güncellendi.',
        ]);
    }

    public function destroy(Product $product, ProductReview $review): JsonResponse
    {
        abort_unless($review->product_id === $product->id, 404);

        $this->authorize('delete', $review);

        $review->delete();

        return response()->json([
            'message' => 'Yorumunuz silindi.',
        ]);
    }

    public function mine(Request $request, Product $product): JsonResponse
    {
        $this->authorize('create', ProductReview::class);

        $user = $request->user();

        $review = ProductReview::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->with('user:id,name')
            ->first();

        return response()->json([
            'review' => $review ? new ProductReviewResource($review) : null,
            'can_review' => $review === null && $this->productReviewService->canReview($user, $product),
        ]);
    }
}
