<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SmsProviderInterface;
use App\Services\KaveNegarSmsProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsProviderInterface::class, KaveNegarSmsProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
