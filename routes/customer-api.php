<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'user'], function () {
    Route::post('login', 'Auth\CustomerAuthController@login');
    Route::post('register', 'Auth\CustomerAuthController@register');

    Route::middleware(['auth:customers', 'customer.enable'])->group(function () {
        Route::get('profile', 'Auth\CustomerAuthController@getProfile');
        Route::post('refresh-token', 'Auth\CustomerAuthController@refreshToken');
        Route::post('logout', 'Auth\CustomerAuthController@logout');

        Route::get('addresses/get-primary', 'Customer\AddressController@getPrimaryAddress');
        Route::patch('addresses/{slug}/set-primary', 'Customer\AddressController@setPrimaryAddress');
        Route::resource('addresses', 'Customer\AddressController');

        Route::get('shops', 'Customer\ShopController@index');
        Route::get('shops/favorites', 'Customer\ShopController@getFavoriteShops');
        Route::get('shops/{slug}', 'Customer\ShopController@show');
        Route::post('shops/{slug}/set-favorite', 'Customer\ShopController@setFavoriteShop');
        Route::post('shops/{slug}/remove-favorite', 'Customer\ShopController@removeFavoriteShop');

        /* Restaurant */
        Route::get('restaurants', 'Customer\RestaurantController@index');
        Route::get('restaurants/favorites', 'Customer\RestaurantController@getFavoriteRestaurants');
        Route::get('restaurants/{slug}', 'Customer\RestaurantController@show');
        Route::post('restaurants/{slug}/favorites', 'Customer\RestaurantController@setFavoriteRestaurant');
        Route::delete('restaurants/{slug}/favorites', 'Customer\RestaurantController@removeFavoriteRestaurant');

        Route::get('restaurants/{slug}/branches', 'Customer\RestaurantBranchController@getRestaurantBranchesByRestaurant');

        Route::get('restaurant-branches', 'Customer\RestaurantBranchController@index');
        Route::get('restaurant-branches/{slug}', 'Customer\RestaurantBranchController@show');
        Route::get('restaurant-branches/{slug}/menus', 'Customer\RestaurantBranchController@getAvailableMenusByBranch');

        Route::get('restaurant-categories', 'Customer\RestaurantCategoryController@index');
        Route::get('restaurant-categories/{slug}/restaurants', 'Customer\RestaurantCategoryController@getRestaurantsByCategory');

        Route::get('restaurant-tags', 'Customer\RestaurantTagController@index');
        Route::get('restaurant-tags/{slug}/restaurants', 'Customer\RestaurantTagController@getRestaurantsByTag');
        /* Restaurant */

        Route::resource('orders', 'Customer\OrderController');
    });
});
