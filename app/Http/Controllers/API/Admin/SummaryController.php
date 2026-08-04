<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\AdminOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SummaryController extends Controller
{
    public function __construct(private AdminOrderService $adminOrderService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('adminViewAny', Order::class);

        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'summary' => $this->adminOrderService->summaryFor($user),
        ]);
    }
}
