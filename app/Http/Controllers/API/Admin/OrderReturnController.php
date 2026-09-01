<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewOrderReturnRequest;
use App\Http\Resources\Api\OrderReturnRequestResource;
use App\Models\OrderReturnRequest;
use App\Models\User;
use App\Services\OrderReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;

class OrderReturnController extends Controller
{
    public function __construct(private OrderReturnService $returnService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OrderReturnRequest::class);

        /** @var User $user */
        $user = $request->user();

        $status = $request->query('status');

        $query = $this->returnService->requestsQueryFor($user);

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $requests = $query->paginate(20);

        return response()->json([
            'return_requests' => OrderReturnRequestResource::collection($requests->items()),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
            'pending_count' => $this->returnService->pendingCountFor($user),
        ]);
    }

    public function approve(
        ReviewOrderReturnRequest $request,
        OrderReturnRequest $returnRequest,
    ): JsonResponse {
        $this->authorize('approve', $returnRequest);

        try {
            $updated = $this->returnService->approve(
                $returnRequest,
                Auth::user(),
                $request->validated('admin_note'),
            );
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'return_request' => new OrderReturnRequestResource($updated),
            'message' => 'Talep onaylandı. İade kargo etiketi hazır.',
        ]);
    }

    public function reject(
        ReviewOrderReturnRequest $request,
        OrderReturnRequest $returnRequest,
    ): JsonResponse {
        $this->authorize('reject', $returnRequest);

        try {
            $updated = $this->returnService->reject(
                $returnRequest,
                Auth::user(),
                $request->validated('admin_note'),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'return_request' => new OrderReturnRequestResource($updated),
            'message' => 'İade talebi reddedildi.',
        ]);
    }

    public function receive(OrderReturnRequest $returnRequest): JsonResponse
    {
        $this->authorize('receive', $returnRequest);

        try {
            $updated = $this->returnService->receive($returnRequest, Auth::user());
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $message = $updated->isExchange()
            ? 'Ürün teslim alındı. Stok güncellendi ve değişim kargosu oluşturuldu.'
            : 'Ürün teslim alındı. Stok girişi yapıldı ve ödeme iade edildi.';

        return response()->json([
            'return_request' => new OrderReturnRequestResource($updated),
            'message' => $message,
        ]);
    }
}
