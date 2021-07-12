<?php

namespace App\Providers;

use App\Services\BoomSmsService;
use App\Services\MessagingService;
use App\Services\SlackMessagingService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('production')) {
            $this->app->singleton(MessagingService::class, function ($app) {
                return new BoomSmsService();
            });
        } else {
            $this->app->singleton(MessagingService::class, function ($app) {
                return new SlackMessagingService();
            });
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
