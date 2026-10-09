<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Services\PayMongoService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PaymentGateway::class,
            PayMongoService::class
        );
    }

    public function boot(): void
    {
        //
    }
}
