<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v3/admin',
    'namespace' => '\App\\Http\\Controllers\\Admin\\v3',
    'middleware' => ['cors', 'json.response', 'auth:users', 'user.enable'],
], function () {
    /* Shop Main Category */
    Route::resource('shop-main-categories', 'ShopMainCategoryController', ['as' => 'admin-v3-shop-main-category', 'except' => ['create', 'edit']]);
    Route::put('shop-main-categories/{shopMainCategory}/index', 'ShopMainCategoryController@updateSearchIndex');
    /* Shop Main Category */

    Route::get('shop-main-categories/{shopMainCategory:slug}/shop-categories', 'ShopCategoryController@getCategoriesByMainCategory');

    /* Restaurant Order */
    Route::resource('restaurant-orders', 'RestaurantOrderController', ['as' => 'admin-v3-restaurant', 'except' => ['create', 'edit']]);
    Route::post('restaurant-orders/{restaurantOrder}/status', 'RestaurantOrderController@changeStatus');
    Route::put('restaurant-orders/{restaurantOrder}/payment', 'RestaurantOrderController@updatePayment');
    Route::delete('restaurant-orders/{restaurantOrder}/restaurant-order-items/{restaurantOrderItem}/cancel', 'RestaurantOrderController@cancelOrderItem');
    /* Restaurant Order */

    /* Shop Order */
    Route::resource('shop-orders', 'ShopOrderController', ['as' => 'admin-v3-shop', 'except' => ['create', 'edit']]);
    Route::post('shop-orders/{shopOrder}/status', 'ShopOrderController@changeStatus');
    Route::put('shop-orders/{shopOrder}/payment', 'ShopOrderController@updatePayment');
    Route::delete('shop-orders/{shopOrder}/shop-order-items/{shopOrderItem}/cancel', 'ShopOrderController@cancelOrderItem');
    /* Shop Order */

    /* Menu Option */
    Route::resource('menus/{menu}/options', 'MenuOptionController', ['as' => 'admin-v3-menu-option', 'except' => ['create', 'edit']]);
    Route::resource('options/{option}/items', 'MenuOptionItemController', ['as' => 'admin-v3-menu-option-item', 'except' => ['create', 'edit']]);
    /* Menu Option */

    Route::get('customers/{customer}/credits', 'CreditController@index');
    Route::post('customers/{customer}/credits', 'CreditController@updateOrCreate');
    Route::delete('customers/{customer}/credits', 'CreditController@delete');

    Route::get('histories/search', 'SearchHistoryController@index');

    Route::resource('audit-logs', 'AuditLogsController', ['as' => 'admin-v3-audit-logs', 'except' => ['create', 'edit']]);
});
