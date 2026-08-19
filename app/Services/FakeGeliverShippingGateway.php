<?php

namespace App\Services;

use App\Contracts\ShippingGateway;
use App\DataTransferObjects\ShipmentCreationResult;
use App\Models\Order;
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
        );
    }
}
