<?php

namespace App\Services;

use App\Contracts\ShippingGateway;
use App\DataTransferObjects\ShipmentCreationResult;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class FakeGeliverShippingGateway implements ShippingGateway
{
    public function createShipment(Order $order): ShipmentCreationResult
    {
        $shipmentId = 'fake-shipment-'.Str::uuid()->toString();
        $trackingNumber = 'FAKE'.str_pad((string) $order->id, 10, '0', STR_PAD_LEFT);

        return new ShipmentCreationResult(
            shipmentId: $shipmentId,
            trackingNumber: $trackingNumber,
            trackingUrl: 'https://tracking.example.test/'.$trackingNumber,
            labelUrl: 'https://tracking.example.test/labels/'.$shipmentId.'.pdf',
            estimatedDeliveryAt: Carbon::now()->addDays(3)->toIso8601String(),
        );
    }

    public function createReturnShipment(Order $order): ShipmentCreationResult
    {
        $shipmentId = 'fake-return-'.Str::uuid()->toString();
        $trackingNumber = 'FAKERET'.str_pad((string) $order->id, 8, '0', STR_PAD_LEFT);

        return new ShipmentCreationResult(
            shipmentId: $shipmentId,
            trackingNumber: $trackingNumber,
            trackingUrl: 'https://tracking.example.test/'.$trackingNumber,
            labelUrl: 'https://tracking.example.test/labels/'.$shipmentId.'.pdf',
        );
    }
}
