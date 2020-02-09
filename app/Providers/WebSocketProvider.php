<?php

namespace App\Providers;

use App\Http\Controllers\WebSocketController;
use Illuminate\Support\ServiceProvider;

class WebSocketProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
        $this->app->singleton(WebSocketController::class, function ($app) {
            return new WebSocketController();
        });
    }
}
