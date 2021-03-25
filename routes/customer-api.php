<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'user'], function () {
    Route::post('login', 'Auth\CustomerAuthController@login');
    Route::post('register', 'Auth\CustomerAuthController@register');
    Route::post('send-otp', 'Auth\OtpController@sendOtpToRegister');

    Route::post('forgot-password', 'Auth\OtpController@forgotPassword');
    Route::post('reset-password', 'Auth\CustomerAuthController@resetPassword');

    /* Home */
    Route::get('suggestions', 'Customer\HomeController@getSuggestions');
    Route::get('new-arrivals', 'Customer\HomeController@getNewArrivals');
    Route::get('search', 'Customer\HomeController@search');
    /* Home */

    /* Restaurant */
    Route::get('restaurants/new-arrivals', 'Customer\RestaurantController@getNewArrivals');
    Route::get('restaurants/recommendations', 'Customer\RestaurantController@getRecommendations');

    Route::get('restaurants/branches', 'Customer\RestaurantController@getAllBranches');
    Route::get('restaurants/branches/search', 'Customer\HomeController@searchRestaurantBranches');
    Route::get('restaurants/branches/{slug}', 'Customer\RestaurantController@getOneBranch');
    Route::get('restaurants/branches/{slug}/menus', 'Customer\RestaurantController@getAvailableMenusByBranch');

    Route::get('restaurant-categories', 'Customer\RestaurantController@getCategories');
    Route::get('restaurant-categories/{slug}/restaurants', 'Customer\RestaurantController@getByCategory');

    Route::get('restaurant-tags', 'Customer\RestaurantController@getTags');
    Route::get('restaurant-tags/{slug}/restaurants', 'Customer\RestaurantController@getByTag');
    /* Restaurant */

    /* Shop */
    Route::get('shops', 'Customer\ShopController@index');
    Route::get('shops/categories', 'Customer\ShopController@getCategories');
    Route::get('shop/categorized/products', 'Customer\ShopController@getCatgorizedProduct');
    Route::get('shops/tags', 'Customer\ShopController@getTags');
    Route::get('shop-tags/{slug}', 'Customer\ShopController@getByTag');
    Route::get('shop-categories/{slug}', 'Customer\ShopController@getByCategory');
    Route::get('shop-subcategories/{slug}', 'Customer\ShopController@getBySubCategory');
    Route::get('shops/{slug}', 'Customer\ShopController@show');
    /* Shop */

    /* Product */
    Route::get('products', 'Customer\ProductController@index');
    Route::get('products/brands', 'Customer\ProductController@getAllBrand');
    Route::get('products/search', 'Customer\HomeController@searchProduct');
    Route::get('products/{slug}', 'Customer\ProductController@show');
    Route::get('product-categories/{slug}', 'Customer\ProductController@getByCategory');
    Route::get('product-shops/{slug}', 'Customer\ProductController@getByShop');
    Route::get('product-brands/{slug}', 'Customer\ProductController@getByBrand');
    /* Product */

    Route::middleware(['auth:customers', 'customer.enable'])->group(function () {
        Route::get('profile', 'Auth\CustomerAuthController@getProfile');
        Route::put('profile', 'Auth\CustomerAuthController@updateProfile');
        Route::patch('password/update', 'Auth\CustomerAuthController@updatePassword');
        Route::post('refresh-token', 'Auth\CustomerAuthController@refreshToken');
        Route::post('logout', 'Auth\CustomerAuthController@logout');

        /* regist device token */
        Route::post('/register-device', 'Customer\HomeController@registerCustomerToken');

        Route::get('townships', 'Customer\AddressController@getAllTownships');
        Route::get('addresses/get-primary', 'Customer\AddressController@getPrimaryAddress');
        Route::patch('addresses/{slug}/set-primary', 'Customer\AddressController@setPrimaryAddress');
        Route::resource('addresses', 'Customer\AddressController', ['as' => 'customer.addresses']);

        Route::get('favorites-count', 'Auth\CustomerAuthController@getFavoritesCount');

        /* Restaurant */
        // Route::get('restaurants', 'Customer\RestaurantController@index');
        Route::get('restaurants/favorites', 'Customer\RestaurantController@getFavoriteRestaurants');
        Route::post('restaurants/{slug}/favorites', 'Customer\RestaurantController@setFavoriteRestaurant');
        Route::delete('restaurants/{slug}/favorites', 'Customer\RestaurantController@removeFavoriteRestaurant');

        Route::resource('restaurants/orders', 'Customer\RestaurantOrderController', ['as' => 'customer.restaurants']);
        Route::post('restaurants/ratings', 'Customer\RestaurantRatingController@store');
        /* Restaurant */

        /* Shop */
        Route::post('shops/{slug}/set-favorite', 'Customer\ShopController@setFavoriteShop');
        Route::post('shops/{slug}/remove-favorite', 'Customer\ShopController@removeFavoriteShop');
        Route::post('shop/ratings', 'Customer\ShopRatingController@store');
        /* Shop */

        //Route::resource('orders', 'Customer\OrderController');

        /* Product */
        Route::get('products/favorites', 'Customer\ProductController@getFavorite');
        Route::post('products/{slug}/set-favorite', 'Customer\ProductController@setFavorite');
        Route::post('products/{slug}/remove-favorite', 'Customer\ProductController@removeFavorite');
        /* Product */

        Route::resource('shop-orders', 'Customer\ShopOrderController', ['as' => 'customer']);
        Route::put('shop-orders/cancel/{slug}', 'Customer\ShopOrderController@cancelOrder');

        Route::get('promocode', 'Customer\PromocodeController@index');
        Route::get('promocode/validate/{slug}', 'Customer\PromocodeController@validatePromoCode');

    });
});
