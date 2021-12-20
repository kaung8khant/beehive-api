<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v3',
    'middleware' => ['cors', 'json.response'],
], function () {
    Route::get('images/{size}/{fileName}', 'File\v3\FileController@getImage');

    Route::middleware(['auth:customers', 'customer.enable'])->group(function () {
        Route::post('carts', 'Cart\CartController@viewCart');

        /* Shop Cart */
        Route::post('shops/carts/products/{product}', 'Cart\ShopCartController@store');
        Route::put('shops/carts/products/{product}', 'Cart\ShopCartController@updateQuantity');
        Route::put('shops/carts/address', 'Cart\ShopCartController@updateAddress');

        Route::delete('shops/carts/products/{product}', 'Cart\ShopCartController@delete');
        Route::delete('shops/carts', 'Cart\ShopCartController@deleteCart');

        Route::post('shops/carts/promocode', 'Cart\ShopCartController@applyPromocode');
        Route::delete('shops/carts/promocode', 'Cart\ShopCartController@removePromocode');

        Route::post('shops/carts/checkout', 'Cart\ShopCartController@checkout');
        /* Shop Cart */

        /* Restaurant Cart */
        Route::post('restaurants/carts/menus/{menu}', 'Cart\RestaurantCartController@store');
        Route::put('restaurants/carts/menus/{menu}', 'Cart\RestaurantCartController@updateQuantity');
        Route::put('restaurants/carts/address', 'Cart\RestaurantCartController@updateAddress');

        Route::delete('restaurants/carts/menus/{menu}', 'Cart\RestaurantCartController@delete');
        Route::delete('restaurants/carts', 'Cart\RestaurantCartController@deleteCart');

        Route::post('restaurants/carts/promocode', 'Cart\RestaurantCartController@applyPromocode');
        Route::delete('restaurants/carts/promocode', 'Cart\RestaurantCartController@removePromocode');

        Route::post('restaurants/carts/checkout', 'Cart\RestaurantCartController@checkout');
        /* Restaurant Cart */

    });

    Route::get('versions', 'SettingsController@getAppVersions');

    Route::get('restaurants/invoice/{slug}/generate', 'Pdf\RestaurantInvoiceController@generateInvoice');
    Route::get('shops/invoice/{slug}/generate', 'Pdf\ShopInvoiceController@generateInvoice');

    Route::get('restaurants/invoices/{fileName}', 'Pdf\RestaurantInvoiceController@getInvoice');
    Route::get('shops/invoices/{fileName}', 'Pdf\ShopInvoiceController@getInvoice');
});
