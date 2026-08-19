<?php

namespace App\Contracts;

use App\DataTransferObjects\ShipmentCreationResult;
use App\Models\Order;

interface ShippingGateway
{
    public function createShipment(Order $order): ShipmentCreationResult;
}
