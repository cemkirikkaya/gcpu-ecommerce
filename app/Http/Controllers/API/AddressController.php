<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Http\Resources\Api\AddressResource;
use App\Models\Address;
use App\Services\AddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct(private AddressService $addressService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Address::class);

        $addresses = $request->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'addresses' => AddressResource::collection($addresses),
        ]);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $this->authorize('create', Address::class);

        $address = $this->addressService->create(
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'address' => new AddressResource($address),
            'message' => 'Adres kaydedildi.',
        ], 201);
    }

    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        $this->authorize('update', $address);

        $address = $this->addressService->update($address, $request->validated());

        return response()->json([
            'address' => new AddressResource($address),
            'message' => 'Adres güncellendi.',
        ]);
    }

    public function destroy(Address $address): JsonResponse
    {
        $this->authorize('delete', $address);

        $this->addressService->delete($address);

        return response()->json([
            'message' => 'Adres silindi.',
        ]);
    }

    public function setDefault(Request $request, Address $address): JsonResponse
    {
        $this->authorize('update', $address);

        $address = $this->addressService->setDefault($address);

        return response()->json([
            'address' => new AddressResource($address),
            'message' => 'Varsayılan adres güncellendi.',
        ]);
    }
}
