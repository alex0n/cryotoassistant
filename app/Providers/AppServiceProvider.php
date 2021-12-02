<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Binance as BinanceService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(BinanceService::class, function ($app) {
            return new BinanceService();
        });
    }
}
