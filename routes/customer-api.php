<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'user'], function () {
    Route::post('login', 'Auth\CustomerAuthController@login');
    Route::post('register', 'Auth\CustomerAuthController@register');

    Route::middleware(['auth:customers', 'customer.enable'])->group(function () {
        Route::get('customer-detail', 'Auth\CustomerAuthController@getAuthenticatedCustomer');
        Route::post('refresh-token', 'Auth\CustomerAuthController@refreshToken');
        Route::post('logout', 'Auth\CustomerAuthController@logout');

        Route::get('addresses/get-primary', 'Customer\AddressController@getPrimaryAddress');
        Route::patch('addresses/{slug}/set-primary', 'Customer\AddressController@setPrimaryAddress');
        Route::resource('addresses', 'Customer\AddressController');

        Route::post('shop/{slug}/set-favorite', 'Customer\ShopController@setFavoriteShop');
        Route::post('shop/{slug}/remove-favorite', 'Customer\ShopController@removeFavoriteShop');

        Route::post('restaurant/{slug}/set-favorite', 'Customer\RestaurantController@setFavoriteRestaurant');
        Route::post('restaurant/{slug}/remove-favorite', 'Customer\RestaurantController@removeFavoriteRestaurant');

    });
});