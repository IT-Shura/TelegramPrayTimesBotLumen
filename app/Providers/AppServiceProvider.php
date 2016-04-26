<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Bot;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Bot', function ($app) {
          return new Bot;
        });
    }
}
