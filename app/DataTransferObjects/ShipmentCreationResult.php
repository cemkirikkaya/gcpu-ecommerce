<?php

namespace App\DataTransferObjects;

readonly class ShipmentCreationResult
{
    public function __construct(
        public string $shipmentId,
        public ?string $trackingNumber,
        public ?string $trackingUrl,
        public ?string $labelUrl = null,
        public ?string $estimatedDeliveryAt = null,
    ) {}
}
