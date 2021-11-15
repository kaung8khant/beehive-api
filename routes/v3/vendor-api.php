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

    /* Menu Option */
    Route::resource('menus/{menu}/options', 'MenuOptionController', ['as' => 'vendor-v3-menu-option', 'except' => ['create', 'edit']]);
    Route::resource('options/{option}/items', 'MenuOptionItemController', ['as' => 'vendor-v3-menu-option-item', 'except' => ['create', 'edit']]);
    /* Menu Option */
});
