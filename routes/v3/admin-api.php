<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v3/admin',
    'namespace' => '\App\\Http\\Controllers\\Admin\\v3',
    'middleware' => ['cors', 'json.response', 'auth:users', 'user.enable'],
], function () {
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
    Route::resource('menu-options', 'MenuOptionController', ['as' => 'admin-v3-menu-option', 'except' => ['create', 'edit']]);
    Route::get('menus/{menu}/menu-options', 'MenuOptionController@index');

    Route::resource('menu-option-items', 'MenuOptionItemController', ['as' => 'admin-v3-menu-option-item', 'except' => ['create', 'edit']]);
    Route::get('menu-options/{menuOption}/items', 'MenuOptionItemController@index');
    /* Menu Option */

    Route::get('customers/{customer}/credits', 'CreditController@index');
    Route::post('customers/{customer}/credits', 'CreditController@updateOrCreate');
    Route::delete('customers/{customer}/credits', 'CreditController@delete');

    Route::get('histories/search', 'SearchHistoryController@index');

    Route::resource('audit-logs', 'AuditLogsController', ['as' => 'admin-v3-audit-logs', 'except' => ['create', 'edit']]);
});

Route::group(['prefix' => 'v3', 'middleware' => ['cors', 'json.response']], function () {
    Route::post('carts', 'Cart\CartController@viewCart');

    /* Restaurant Cart */
    Route::post('restaurants/carts/menus/{menu}', 'Cart\RestaurantCartController@store');
    Route::put('restaurants/carts/menus/{menu}', 'Cart\RestaurantCartController@updateQuantity');

    Route::delete('restaurants/carts/menus/{menu}', 'Cart\RestaurantCartController@delete');
    Route::delete('restaurants/carts', 'Cart\RestaurantCartController@deleteCart');

    Route::post('restaurants/carts/promocode', 'Cart\RestaurantCartController@applyPromocode');
    Route::delete('restaurants/carts/promocode', 'Cart\RestaurantCartController@removePromocode');

    Route::put('restaurants/carts/address', 'Cart\RestaurantCartController@updateAddress');
    Route::post('restaurants/carts/checkout', 'Cart\RestaurantCartController@checkout');
    /* Restaurant Cart */

    /* Shop Cart */
    Route::post('shops/carts/products/{product}', 'Cart\ShopCartController@store');
    Route::put('shops/carts/products/{product}', 'Cart\ShopCartController@updateQuantity');

    Route::delete('shops/carts/products/{product}', 'Cart\ShopCartController@delete');
    Route::delete('shops/carts', 'Cart\ShopCartController@deleteCart');

    Route::post('shops/carts/promocode', 'Cart\ShopCartController@applyPromocode');
    Route::delete('shops/carts/promocode', 'Cart\ShopCartController@removePromocode');

    Route::put('shops/carts/address', 'Cart\ShopCartController@updateAddress');
    Route::post('shops/carts/checkout', 'Cart\ShopCartController@checkout');
    /* Shop Cart */

    Route::get('versions', 'SettingsController@getAppVersions');

    Route::get('restaurants/invoice/{slug}/generate', 'Pdf\RestaurantInvoiceController@generateInvoice');
    Route::get('shops/invoice/{slug}/generate', 'Pdf\ShopInvoiceController@generateInvoice');
});
