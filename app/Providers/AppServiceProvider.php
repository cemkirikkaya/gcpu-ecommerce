<?php

namespace App\Providers;

use App\Contracts\GoogleIdTokenVerifier;
use App\Contracts\PaymentGateway;
use App\Contracts\ShippingGateway;
use App\Enums\PaymentProvider;
use App\Services\FakeGeliverShippingGateway;
use App\Services\FakePaymentGateway;
use App\Services\FakeStripePaymentGateway;
use App\Services\GeliverShippingGateway;
use App\Services\GoogleAuthService;
use App\Services\IyzicoPaymentGateway;
use App\Services\PaymentGatewayFactory;
use App\Services\ShippingGatewayFactory;
use App\Services\SocialiteGoogleIdTokenVerifier;
use App\Services\StripePaymentGateway;
use App\View\Composers\ShopLayoutComposer;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FakePaymentGateway::class);
        $this->app->singleton(FakeStripePaymentGateway::class);
        $this->app->singleton(IyzicoPaymentGateway::class);
        $this->app->singleton(StripePaymentGateway::class);
        $this->app->singleton(PaymentGatewayFactory::class);
        $this->app->singleton(FakeGeliverShippingGateway::class);
        $this->app->singleton(GeliverShippingGateway::class);
        $this->app->singleton(ShippingGatewayFactory::class);

        $this->app->singleton(GoogleIdTokenVerifier::class, SocialiteGoogleIdTokenVerifier::class);
        $this->app->singleton(GoogleAuthService::class);

        $this->app->singleton(PaymentGateway::class, function ($app): PaymentGateway {
            return $app->make(PaymentGatewayFactory::class)->make(PaymentProvider::Iyzico);
        });

        $this->app->singleton(ShippingGateway::class, function ($app): ShippingGateway {
            return $app->make(ShippingGatewayFactory::class)->make();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        View::composer('layouts.app', ShopLayoutComposer::class);
    }
}
