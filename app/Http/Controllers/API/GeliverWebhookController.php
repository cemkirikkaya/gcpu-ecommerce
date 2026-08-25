<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderShipmentService;
use Geliver\Webhooks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        Log::info('Geliver webhook received', [
            'event' => $payload['event'] ?? null,
        ]);

        if (! $this->isTrackingEvent(isset($payload['event']) ? (string) $payload['event'] : null)) {
            return response()->json([
                'message' => 'Webhook alındı.',
            ]);
        }

        /** @var array<string, mixed> $data */
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        $shipmentId = $this->resolveShipmentId($data);

        if ($shipmentId === null) {
            Log::warning('Geliver webhook missing shipment id', [
                'event' => $payload['event'] ?? null,
            ]);

            return response()->json([
                'message' => 'Webhook alındı.',
            ]);
        }

        $order = Order::query()
            ->where('geliver_shipment_id', $shipmentId)
            ->first();

        if ($order === null) {
            Log::warning('Geliver webhook order not found', [
                'shipment_id' => $shipmentId,
            ]);

            return response()->json([
                'message' => 'Webhook alındı.',
            ]);
        }

        $this->orderShipmentService->syncFromWebhook($order, $data);

        Log::info('Geliver webhook synced order', [
            'order_id' => $order->id,
            'shipment_id' => $shipmentId,
            'status' => $order->fresh()->status->value,
        ]);

        return response()->json([
            'message' => 'Takip bilgileri güncellendi.',
        ]);
    }

    private function isTrackingEvent(?string $event): bool
    {
        if ($event === null || $event === '') {
            return false;
        }

        $normalized = strtoupper(str_replace('-', '_', $event));

        return in_array($normalized, ['TRACK_UPDATED', 'TRACK_CREATED'], true);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveShipmentId(array $data): ?string
    {
        foreach (['id', 'shipmentId', 'shipmentID'] as $key) {
            if (! isset($data[$key])) {
                continue;
            }

            $shipmentId = (string) $data[$key];

            if ($shipmentId !== '') {
                return $shipmentId;
            }
        }

        return null;
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
