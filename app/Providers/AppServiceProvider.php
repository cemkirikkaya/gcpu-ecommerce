<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Services\FakePaymentGateway;
use App\Services\IyzicoPaymentGateway;
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
        $this->app->singleton(PaymentGateway::class, function (): PaymentGateway {
            if (config('iyzico.fake')) {
                return new FakePaymentGateway;
            }

            return new IyzicoPaymentGateway;
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
