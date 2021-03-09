<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'vendor'], function () {
    Route::post('login', 'Auth\VendorAuthController@login');
    Route::post('register', 'Auth\VendorAuthController@register');
    Route::middleware(['auth:vendors', 'user.enable'])->group(function () {
        Route::get('profile', 'Auth\VendorAuthController@getProfile');
        Route::put('profile/update', 'Auth\VendorAuthController@updateProfile');
        Route::post('refresh-token', 'Auth\VendorAuthController@refreshToken');
        Route::post('logout', 'Auth\VendorAuthController@logout');

        /* restaurant categories */
        Route::get('restaurants/{slug}/restaurant-categories', 'RestaurantCategoryController@getCategoriesByRestaurant');
        /* restaurant categories */

        /* menus */
        Route::get('restaurant-branches/{slug}/menus', 'MenuController@getAvailableMenusByRestaurantBranch');
        Route::get('menus/{slug}', 'MenuController@show');
        Route::post('menus', 'MenuController@store');
        Route::put('menus/{slug}', 'MenuController@update');
        Route::delete('menus/{slug}', 'MenuController@destory');
        Route::patch('menus/toggle-enable/{slug}', 'MenuController@toggleEnable');
        Route::post('restaurant-branches/add-available-menus/{slug}', 'RestaurantBranchController@addAvailableMenus');
        Route::post('restaurant-branches/remove-available-menus/{slug}', 'RestaurantBranchController@removeAvailableMenus');
        Route::get('menus/{slug}/menu-variations', 'MenuVariationController@getVariationsByMenu');
        Route::get('menus/{slug}/menu-toppings', 'MenuToppingController@getToppingsByMenu');
        /* menus */

        /* restaurant */
    });
});
