<?php

namespace App\Providers;

use App\Services\Payment\IdpayGateway;
use App\Services\Payment\PayirGateway;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\ZarinpalGateway;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // ثبت درگاه‌های پرداخت به صورت Singleton
        $this->app->singleton(ZarinpalGateway::class, function ($app) {
            return new ZarinpalGateway();
        });
        
        $this->app->singleton(PayirGateway::class, function ($app) {
            return new PayirGateway();
        });
        
        $this->app->singleton(IdpayGateway::class, function ($app) {
            return new IdpayGateway();
        });
        
        // ثبت درگاه پیش‌فرض
        $this->app->bind(PaymentGatewayInterface::class, ZarinpalGateway::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
} 