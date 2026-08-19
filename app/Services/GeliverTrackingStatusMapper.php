<?php

namespace App\Services;

use App\Enums\OrderStatus;

class GeliverTrackingStatusMapper
{
    /**
     * @param  array<string, mixed>  $shipmentData
     */
    public function resolveOrderStatus(array $shipmentData): ?OrderStatus
    {
        /** @var array<string, mixed> $trackingStatus */
        $trackingStatus = is_array($shipmentData['trackingStatus'] ?? null)
            ? $shipmentData['trackingStatus']
            : [];

        $codes = array_filter([
            isset($shipmentData['statusCode']) ? strtoupper((string) $shipmentData['statusCode']) : null,
            isset($trackingStatus['trackingStatusCode']) ? strtoupper((string) $trackingStatus['trackingStatusCode']) : null,
            isset($trackingStatus['trackingSubStatusCode']) ? strtoupper((string) $trackingStatus['trackingSubStatusCode']) : null,
        ]);

        foreach ($codes as $code) {
            if ($this->isDelivered($code)) {
                return OrderStatus::Delivered;
            }
        }

        foreach ($codes as $code) {
            if ($this->isShipped($code)) {
                return OrderStatus::Shipped;
            }
        }

        return null;
    }

    private function isDelivered(string $code): bool
    {
        return in_array($code, [
            'DELIVERED',
            'DELIVERY',
        ], true);
    }

    private function isShipped(string $code): bool
    {
        return in_array($code, [
            'SHIPPED',
            'IN_TRANSIT',
            'OUT_FOR_DELIVERY',
            'PRE_TRANSIT',
            'LABEL_PRINTED',
            'TRACKING_CODE_CREATED',
            'OFFER_ACCEPTED',
            'INFORMATION_RECEIVED',
            'TRANSIT',
        ], true);
    }
}
