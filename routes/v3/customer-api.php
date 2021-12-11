<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v3/user',
    'namespace' => '\App\\Http\\Controllers\\Customer\\v3',
    'middleware' => ['cors', 'json.response'],
], function () {
    Route::middleware(['auth:customers', 'customer.enable'])->group(function () {
        Route::resource('restaurant-orders', 'RestaurantOrderController', ['as' => 'customer-v3-restaurant', 'except' => ['create', 'edit']]);
        Route::resource('shop-orders', 'Shop\ShopOrderController', ['as' => 'customer-v3-shop', 'except' => ['create', 'edit']]);

        Route::post('kbz/pay/{orderType}/{slug}', '\App\Http\Controllers\Payment\KbzPayController@pay');
        Route::post('cb/pay/{orderType}/{slug}', '\App\Http\Controllers\Payment\CbPayController@pay');
        Route::get('cb/check/{orderType}/{slug}', '\App\Http\Controllers\Payment\CbPayController@checkTransaction');

        //Promo code
        Route::post('promocode/validate', 'PromocodeController@validatePromoCode');

        Route::get('credits', 'CreditController@index');
    });

    /* Shop */
    Route::get('brands', 'Shop\BrandController@getAllBrands');

    Route::get('shop-main-categories', 'Shop\ShopMainCategoryController@index');

    Route::get('brands/{brand}/categories', 'Shop\ShopCategoryController@getByBrand');
    Route::get('shops/{shop}/categories', 'Shop\ShopCategoryController@getByShop');

    Route::get('brands/{brand}/shops', 'Shop\ShopController@getByBrand');

    Route::get('products', 'Shop\ProductController@index');
    Route::get('shops/{shop}/products/arrivals', 'Shop\ProductController@getNewArrivalsByShop');
    Route::get('shops/{shop}/products/discounts', 'Shop\ProductController@getDiscountsByShop');
    Route::get('brands/{brand}/categories/{category}/products', 'Shop\ProductController@getByBrandAndCategory');
    Route::get('shop-sub-categories/{shopSubCategory}/products', 'Shop\ProductController@getByShopSubCategory');
    /* Shop */

    /* Restaurant */
    Route::get('restaurants/branches', 'Restaurant\RestaurantBranchController@index');
    Route::get('restaurants/branches/{restaurantBranch}/menus', 'Restaurant\MenuController@getAvailableMenusByBranch');

    Route::get('restaurants/favorites', 'Restaurant\RestaurantBranchController@getFavoriteRestaurants');
    /* Restaurant */

    /* Ads */
    Route::get('brands/{brand}/ads', 'AdsController@getByBrand');
    Route::get('shops/{shop}/ads', 'AdsController@getByShop');
    /* Ads */

    Route::get('histories/search', 'SearchHistoryController@index');
    Route::post('histories/clear', 'SearchHistoryController@clearHistory');
});
