<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Http\Resources\Api\AdminCouponResource;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Coupon::class);

        $coupons = Coupon::query()
            ->latest()
            ->get();

        return response()->json([
            'coupons' => AdminCouponResource::collection($coupons),
        ]);
    }

    public function show(Coupon $coupon): JsonResponse
    {
        $this->authorize('view', $coupon);

        return response()->json([
            'coupon' => new AdminCouponResource($coupon),
        ]);
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        $this->authorize('create', Coupon::class);

        $coupon = Coupon::query()->create($request->validated());

        return response()->json([
            'coupon' => new AdminCouponResource($coupon),
            'message' => 'Kupon oluşturuldu.',
        ], 201);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
    {
        $this->authorize('update', $coupon);

        $coupon->update($request->validated());

        return response()->json([
            'coupon' => new AdminCouponResource($coupon->fresh()),
            'message' => 'Kupon güncellendi.',
        ]);
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $this->authorize('delete', $coupon);

        $coupon->delete();

        return response()->json([
            'message' => 'Kupon silindi.',
        ]);
    }
}
