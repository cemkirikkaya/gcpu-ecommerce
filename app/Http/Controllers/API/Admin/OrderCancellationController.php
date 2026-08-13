<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewOrderCancellationRequest;
use App\Http\Resources\Api\OrderCancellationRequestResource;
use App\Models\OrderCancellationRequest;
use App\Models\User;
use App\Services\OrderCancellationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;

class OrderCancellationController extends Controller
{
    public function __construct(private OrderCancellationService $cancellationService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OrderCancellationRequest::class);

        /** @var User $user */
        $user = $request->user();

        $status = $request->query('status');

        $query = $this->cancellationService->requestsQueryFor($user);

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $requests = $query->paginate(20);

        return response()->json([
            'cancellation_requests' => OrderCancellationRequestResource::collection($requests->items()),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
            'pending_count' => $this->cancellationService->pendingCountFor($user),
        ]);
    }

    public function approve(
        ReviewOrderCancellationRequest $request,
        OrderCancellationRequest $cancellationRequest,
    ): JsonResponse {
        $this->authorize('approve', $cancellationRequest);

        try {
            $updated = $this->cancellationService->approve(
                $cancellationRequest,
                Auth::user(),
                $request->validated('admin_note'),
            );
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'cancellation_request' => new OrderCancellationRequestResource($updated),
            'message' => 'İptal talebi onaylandı ve ödeme iade edildi.',
        ]);
    }

    public function reject(
        ReviewOrderCancellationRequest $request,
        OrderCancellationRequest $cancellationRequest,
    ): JsonResponse {
        $this->authorize('reject', $cancellationRequest);

        try {
            $updated = $this->cancellationService->reject(
                $cancellationRequest,
                Auth::user(),
                $request->validated('admin_note'),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'cancellation_request' => new OrderCancellationRequestResource($updated),
            'message' => 'İptal talebi reddedildi.',
        ]);
    }
}
