<?php

namespace App\Providers;

use App\Repositories\Abstracts\DriverRealtimeDataRepositoryInterface;
use App\Repositories\Abstracts\RestaurantOrderDriverStatusRepositoryInterface;
use App\Repositories\DriverRealtimeDataRepository;
use App\Repositories\RestaurantOrderDriverStatusRepository;
use App\Repositories\RestaurantOrder\RestaurantOrderRepository;
use App\Repositories\RestaurantOrder\RestaurantOrderRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(RestaurantOrderRepositoryInterface::class, RestaurantOrderRepository::class);

        $this->app->bind(RestaurantOrderDriverStatusRepositoryInterface::class, RestaurantOrderDriverStatusRepository::class);
        $this->app->bind(DriverRealtimeDataRepositoryInterface::class, DriverRealtimeDataRepository::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
