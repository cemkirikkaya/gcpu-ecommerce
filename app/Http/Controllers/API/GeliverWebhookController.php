<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderShipmentService;
use Geliver\Webhooks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeliverWebhookController extends Controller
{
    public function __construct(private OrderShipmentService $orderShipmentService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();

        if (! Webhooks::verify($rawBody, $this->normalizeHeaders($request), false)) {
            return response()->json([
                'message' => 'Geçersiz webhook isteği.',
            ], 400);
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($rawBody, true) ?: [];

        if (($payload['event'] ?? null) !== 'TRACK_UPDATED') {
            return response()->json([
                'message' => 'Webhook alındı.',
            ]);
        }

        /** @var array<string, mixed> $data */
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        $shipmentId = isset($data['id']) ? (string) $data['id'] : null;

        if ($shipmentId === null || $shipmentId === '') {
            return response()->json([
                'message' => 'Webhook alındı.',
            ]);
        }

        $order = Order::query()
            ->where('geliver_shipment_id', $shipmentId)
            ->first();

        if ($order === null) {
            return response()->json([
                'message' => 'Webhook alındı.',
            ]);
        }

        $this->orderShipmentService->syncFromWebhook($order, $data);

        return response()->json([
            'message' => 'Takip bilgileri güncellendi.',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function normalizeHeaders(Request $request): array
    {
        $headers = [];

        foreach ($request->headers->all() as $name => $values) {
            $headers[$name] = is_array($values) ? ($values[0] ?? '') : (string) $values;
        }

        return $headers;
    }
}
