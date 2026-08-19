<?php

namespace App\Services;

use App\Contracts\ShippingGateway;

class ShippingGatewayFactory
{
    public function __construct(
        private FakeGeliverShippingGateway $fakeGateway,
        private GeliverShippingGateway $geliverGateway,
    ) {}

    public function make(): ShippingGateway
    {
        if (config('geliver.fake')) {
            return $this->fakeGateway;
        }

        return $this->geliverGateway;
    }
}
