<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v2/user', 'middleware' => ['cors', 'json.response']], function () {
    Route::post('login', 'Auth\CustomerAuthController@login');
    Route::post('register', 'Auth\CustomerAuthController@register');
    Route::post('send-otp', 'Auth\OtpController@sendOtpToRegister');
    Route::post('check-otp', 'Auth\OtpController@checkOtpToRegister');

    Route::get("test", 'Customer\HomeController@test');

    Route::post('forgot-password', 'Auth\OtpController@forgotPassword');
    Route::post('reset-password', 'Auth\CustomerAuthController@resetPassword');

    /* Home */
    Route::get('suggestions', 'Customer\HomeController@getSuggestions');
    Route::get('new-arrivals', 'Customer\HomeController@getNewArrivals');
    Route::get('search', 'Customer\HomeController@search');
    Route::get('favorite', 'Customer\HomeController@getFavorite');
    /* Home */

    /* Restaurant */
    Route::get('restaurants/new-arrivals', 'Customer\RestaurantController@getNewArrivals');
    Route::get('restaurants/recommendations', 'Customer\RestaurantController@getRecommendations');

    Route::get('restaurants/branches', 'Customer\RestaurantController@getAllBranches');
    Route::get('restaurants/branches/search', 'Customer\HomeController@searchRestaurantBranches');
    Route::get('restaurants/branches/{restaurantBranch}', 'Customer\RestaurantController@getOneBranch');
    Route::get('restaurants/branches/{restaurantBranch}/menus', 'Customer\MenuController@getAvailableMenusByBranch');

    Route::get('restaurant-categories', 'Customer\RestaurantController@getCategorizedRestaurants');
    Route::get('restaurant-categories/{category}/restaurants', 'Customer\RestaurantController@getByCategory');

    Route::get('restaurant-tags', 'Customer\RestaurantController@getTags');
    Route::get('restaurant-tags/{slug}/restaurants', 'Customer\RestaurantController@getByTag');
    /* Restaurant */

    /* Shop */
    Route::get('shops', 'Customer\ShopController@index');
    Route::get('shops/categories', 'Customer\ShopController@getCategories');
    Route::get('shop/categorized/products', 'Customer\ShopController@getCatgorizedProduct');
    Route::get('shops/tags', 'Customer\ShopController@getTags');
    Route::get('shop-tags/{shopTag}', 'Customer\ShopController@getByTag');
    Route::get('shop-categories/{shopCategory}', 'Customer\ShopController@getByCategory');
    Route::get('shop-subcategories/{shopSubCategory}', 'Customer\ShopController@getBySubCategory');
    Route::get('shops/{shop}', 'Customer\ShopController@show');
    /* Shop */

    Route::middleware(['auth:customers', 'customer.enable'])->group(function () {
        Route::get('profile', 'Auth\CustomerAuthController@getProfile');
        Route::put('profile', 'Auth\CustomerAuthController@updateProfile');
        Route::patch('password/update', 'Auth\CustomerAuthController@updatePassword');
        Route::post('refresh-token', 'Auth\CustomerAuthController@refreshToken');
        Route::post('logout', 'Auth\CustomerAuthController@logout');

        /* regist device token */
        Route::post('/register-device', 'Customer\HomeController@registerCustomerToken');
        /* regist device token */

        // Route::get('cities', 'Customer\AddressController@getAllCities');
        // Route::get('cities/{city}/townships', 'Customer\AddressController@getTownshipsByCity');

        // Route::get('townships', 'Customer\AddressController@getAllTownships');
        Route::get('addresses/nearest', 'Customer\AddressController@getNearestAddress');
        Route::get('addresses/get-primary', 'Customer\AddressController@getPrimaryAddress');
        Route::patch('addresses/{address}/set-primary', 'Customer\AddressController@setPrimaryAddress');
        Route::resource('addresses', 'Customer\AddressController', ['as' => 'customer', 'except' => ['create', 'edit']]);

        Route::get('favorites-count', 'Auth\CustomerAuthController@getFavoritesCount');

        /* Restaurant */
        Route::get('restaurants/favorites', 'Customer\RestaurantController@getFavoriteRestaurants');
        Route::post('restaurants/{restaurant}/favorites', 'Customer\RestaurantController@setFavoriteRestaurant');
        Route::delete('restaurants/{restaurant}/favorites', 'Customer\RestaurantController@removeFavoriteRestaurant');

        Route::resource('restaurants/orders', 'Customer\RestaurantOrderController', ['as' => 'customer']);
        Route::post('restaurants/ratings', 'Customer\RestaurantRatingController@store');
        /* Restaurant */

        /* Shop */
        Route::resource('shop-orders', 'Customer\ShopOrderController', ['as' => 'customer']);
        Route::put('shop-orders/cancel/{slug}', 'Customer\ShopOrderController@cancelOrder');
        Route::post('shop/ratings', 'Customer\ShopRatingController@store');
        /* Shop */

        /* Product */
        Route::get('products/favorites', 'Customer\ProductController@getFavorite');
        Route::post('products/{product}/favorites', 'Customer\ProductController@setFavorite');
        Route::delete('products/{product}/favorites', 'Customer\ProductController@removeFavorite');
        /* Product */

        Route::get('promocode', 'Customer\PromocodeController@index');
        Route::post('promocode/validate', 'Customer\PromocodeController@validatePromoCode');

        Route::post('devices', 'OneSignal\OneSignalController@registerCustomerDevice');
    });

    /* Product */
    Route::get('products', 'Customer\ProductController@index');
    Route::get('products/brands', 'Customer\ProductController@getAllBrand');
    Route::get('products/search', 'Customer\HomeController@searchProduct');
    Route::get('products/recommendations', 'Customer\ProductController@getRecommendations');
    Route::get('products/{product}', 'Customer\ProductController@show');
    Route::get('product-categories/{category}', 'Customer\ProductController@getByCategory');
    Route::get('product-shops/{shop}', 'Customer\ProductController@getByShop');
    Route::get('product-brands/{brand}', 'Customer\ProductController@getByBrand');
    /* Product */

    /* Ads */
    Route::get('ads', 'Customer\HomeController@getAds');
    /* Ads */
});

Route::group([
    'prefix' => 'v3/user',
    'namespace' => '\App\\Http\\Controllers\\Customer\\v3',
    'middleware' => ['cors', 'json.response'],
], function () {
    Route::middleware(['auth:customers', 'customer.enable'])->group(function () {
        Route::resource('restaurant-orders', 'RestaurantOrderController', ['as' => 'customer-v3-restaurant', 'except' => ['create', 'edit']]);
        Route::resource('shop-orders', 'ShopOrderController', ['as' => 'customer-v3-shop', 'except' => ['create', 'edit']]);

        Route::post('kbz/pay/{orderType}/{slug}', '\App\Http\Controllers\Payment\KbzPayController@pay');
        Route::post('cb/pay/{orderType}/{slug}', '\App\Http\Controllers\Payment\CbPayController@pay');
        Route::get('cb/check/{orderType}/{slug}', '\App\Http\Controllers\Payment\CbPayController@checkTransaction');

        //Promo code
        Route::post('promocode/validate', 'PromocodeController@validatePromoCode');
    });
});
