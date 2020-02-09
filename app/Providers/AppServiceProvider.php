<?php

namespace App\Providers;

use App\Http\Controllers\WebSocketController;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
//    /**
//     * All of the container singletons that should be registered.
//     *
//     * @var array
//     */
//    public $singletons = [
//        WebSocketController::class => WebSocketController::class,
//    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->singleton(WebSocketController::class, function ($app) {
            return new WebSocketController();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        \Validator::extend('unique_couchdb', function ($attribute, $value, $parameters, $validator) {

            $model = $parameters[0];

            if (class_exists($model)) {
                $model = new $model();
                $data = $model->where($attribute, $value);
                if (isset($parameters[2]) && isset($parameters[3])) {
                    $data->where($parameters[2], '<>', $parameters[3]);
                }
                $datum = $data->first();
                if ($datum) {
                    return false;
                }
            }
            return true;
        });
    }
}
