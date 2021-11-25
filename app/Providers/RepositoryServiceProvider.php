<?php

namespace App\Providers;

use App\Repositories\Abstracts\DriverRealtimeDataRepositoryInterface;
use App\Repositories\Abstracts\RestaurantOrderDriverStatusRepositoryInterface;
use App\Repositories\BaseRepository;
use App\Repositories\BaseRepositoryInterface;
use App\Repositories\DriverRealtimeDataRepository;
use App\Repositories\RestaurantOrderDriverStatusRepository;
use App\Repositories\Shop\Brand\BrandRepository;
use App\Repositories\Shop\Brand\BrandRepositoryInterface;
use App\Repositories\Shop\Product\ProductRepository;
use App\Repositories\Shop\Product\ProductRepositoryInterface;
use App\Repositories\Shop\ShopCategory\ShopCategoryRepository;
use App\Repositories\Shop\ShopCategory\ShopCategoryRepositoryInterface;
use App\Repositories\Shop\ShopMainCategory\ShopMainCategoryRepository;
use App\Repositories\Shop\ShopMainCategory\ShopMainCategoryRepositoryInterface;
use App\Repositories\Shop\ShopSubCategory\ShopSubCategoryRepository;
use App\Repositories\Shop\ShopSubCategory\ShopSubCategoryRepositoryInterface;
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
        $this->app->bind(BaseRepositoryInterface::class, BaseRepository::class);

        $this->app->bind(BrandRepositoryInterface::class, BrandRepository::class);
        $this->app->bind(ShopMainCategoryRepositoryInterface::class, ShopMainCategoryRepository::class);
        $this->app->bind(ShopCategoryRepositoryInterface::class, ShopCategoryRepository::class);
        $this->app->bind(ShopSubCategoryRepositoryInterface::class, ShopSubCategoryRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);

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
