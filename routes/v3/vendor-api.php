<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v3/vendor',
    'namespace' => '\App\\Http\\Controllers\\Admin\\v3',
    'middleware' => ['cors', 'json.response', 'auth:vendors', 'user.enable'],
], function () {
    Route::resource('shop-orders', 'ShopOrderController', ['as' => 'vendor-v3-shop', 'except' => ['create', 'edit']]);
    Route::post('shop-orders/{shopOrder}/status', 'ShopOrderController@changeStatus');
    Route::get('shops/{shop}/orders', 'ShopOrderController@getVendorOrders');
    Route::delete('shop-order-items/{shopOrderItem}/cancel', 'ShopOrderController@cancelOrderItem');
    Route::delete('shop-orders/{shopOrder}/shop-order-items/{shopOrderItem}/cancel', 'ShopOrderController@cancelOrderItem');

    Route::resource('restaurant-orders', 'RestaurantOrderController', ['as' => 'vendor-v3-restaurant', 'except' => ['create', 'edit']]);
    Route::post('restaurant-orders/{restaurantOrder}/status', 'RestaurantOrderController@changeStatus');
    Route::get('restaurant-branches/{restaurantBranch}/orders', 'RestaurantOrderController@getBranchOrders');
    Route::delete('restaurant-order-items/{restaurantOrderItem}/cancel', 'RestaurantOrderController@cancelOrderItem');
    Route::delete('restaurant-orders/{restaurantOrder}/restaurant-order-items/{restaurantOrderItem}/cancel', 'RestaurantOrderController@cancelOrderItem');

    Route::put('menu-options/{menuOption}', 'MenuOptionController@update');
    Route::delete('menu-options/{menuOption}', 'MenuOptionController@destroy');
    Route::post('menu-option-items', 'MenuOptionItemController@store');
    Route::put('menu-option-items/{menuOptionItem}', 'MenuOptionItemController@update');
    Route::delete('menu-option-items/{menuOptionItem}', 'MenuOptionItemController@destroy');
});
