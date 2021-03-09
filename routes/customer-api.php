<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'user'], function () {
    Route::post('login', 'Auth\CustomerAuthController@login');
    Route::post('register', 'Auth\CustomerAuthController@register');
    Route::post('send-otp', 'Auth\OtpController@sendOtp');

    Route::middleware(['auth:customers', 'customer.enable'])->group(function () {
        Route::get('profile', 'Auth\CustomerAuthController@getProfile');
        Route::post('refresh-token', 'Auth\CustomerAuthController@refreshToken');
        Route::post('logout', 'Auth\CustomerAuthController@logout');

        Route::get('addresses/get-primary', 'Customer\AddressController@getPrimaryAddress');
        Route::patch('addresses/{slug}/set-primary', 'Customer\AddressController@setPrimaryAddress');
        Route::resource('addresses', 'Customer\AddressController');

        /* Restaurant */
        // Route::get('restaurants', 'Customer\RestaurantController@index');
        Route::get('restaurants/new-arrivals', 'Customer\RestaurantController@getNewArrivals');
        Route::get('restaurants/recommendations', 'Customer\RestaurantController@getRecommendations');

        Route::get('restaurants/favorites', 'Customer\RestaurantController@getFavoriteRestaurants');
        Route::post('restaurants/{slug}/favorites', 'Customer\RestaurantController@setFavoriteRestaurant');
        Route::delete('restaurants/{slug}/favorites', 'Customer\RestaurantController@removeFavoriteRestaurant');

        Route::get('restaurants/branches', 'Customer\RestaurantController@getAllRestaurantBranches');
        Route::get('restaurants/branches/{slug}', 'Customer\RestaurantController@getOneRestaurantBranch');
        Route::get('restaurants/branches/{slug}/menus', 'Customer\RestaurantController@getAvailableMenusByBranch');

        Route::get('restaurant-categories', 'Customer\RestaurantController@getRestaurantCategories');
        Route::get('restaurant-categories/{slug}/restaurants', 'Customer\RestaurantController@getRestaurantsByCategory');

        Route::get('restaurant-tags', 'Customer\RestaurantController@getRestaurantTags');
        Route::get('restaurant-tags/{slug}/restaurants', 'Customer\RestaurantController@getRestaurantsByTag');
        /* Restaurant */

        /* Shop */
        Route::get('shops', 'Customer\ShopController@index');
        Route::get('shops/favorites', 'Customer\ShopController@getFavoriteShops');
        Route::get('shops/categories','Customer\ShopController@getCategories');
        Route::get('shops/tags','Customer\ShopController@getTags');
        Route::get('shop-tags/{slug}','Customer\ShopController@getByTag');
        Route::get('shops/{slug}', 'Customer\ShopController@show');
        Route::post('shops/{slug}/set-favorite', 'Customer\ShopController@setFavoriteShop');
        Route::post('shops/{slug}/remove-favorite', 'Customer\ShopController@removeFavoriteShop');
        
        /* Shop */

        /* Home */
        Route::get('suggestions', 'Customer\HomeController@getSuggestions');
        Route::get('new-arrivals', 'Customer\HomeController@getNewArrivals');
        /* Home */

        Route::resource('orders', 'Customer\OrderController');

        /* Product */
        Route::get('products','Customer\ProductController@index');
        Route::get('products/{slug}', 'Customer\ProductController@show');
        Route::get('product-categories/{slug}','Customer\ProductController@getByCategory');
        
    });
});
