<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentProvider;
use RuntimeException;

class PaymentGatewayFactory
{
    public function __construct(
        private FakePaymentGateway $fakeIyzicoGateway,
        private IyzicoPaymentGateway $iyzicoGateway,
        private FakeStripePaymentGateway $fakeStripeGateway,
        private StripePaymentGateway $stripeGateway,
    ) {}

    public function make(PaymentProvider $provider): PaymentGateway
    {
        return match ($provider) {
            PaymentProvider::Iyzico => $this->resolveIyzicoGateway(),
            PaymentProvider::Stripe => $this->resolveStripeGateway(),
        };
    }

    public function stripe(): StripePaymentGateway|FakeStripePaymentGateway
    {
        return $this->resolveStripeGateway();
    }

    private function resolveIyzicoGateway(): PaymentGateway
    {
        if (config('iyzico.fake')) {
            return $this->fakeIyzicoGateway;
        }

        return $this->iyzicoGateway;
    }

    private function resolveStripeGateway(): StripePaymentGateway|FakeStripePaymentGateway
    {
        if (config('stripe.fake')) {
            return $this->fakeStripeGateway;
        }

        if (blank(config('stripe.secret_key'))) {
            throw new RuntimeException('Stripe yapılandırması eksik.');
        }

        return $this->stripeGateway;
    }
}
