<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'vendor'], function () {
    Route::post('login', 'Auth\VendorAuthController@login');
    Route::post('register', 'Auth\VendorAuthController@register');

    Route::post('forgot-password', 'Auth\OtpController@forgotPassword');
    Route::post('reset-password', 'Auth\VendorAuthController@resetPassword');

    Route::middleware(['auth:vendors', 'user.enable'])->group(function () {
        Route::get('profile', 'Auth\VendorAuthController@getProfile');
        Route::put('profile/update', 'Auth\VendorAuthController@updateProfile');
        Route::patch('password/update', 'Auth\VendorAuthController@updatePassword');
        Route::post('refresh-token', 'Auth\VendorAuthController@refreshToken');
        Route::post('logout', 'Auth\VendorAuthController@logout');

        /* Dashboard */
        Route::get('dashboard/order-data', 'Dashboard\VendorDashboardController@getOrderData');
        Route::get('dashboard/daywise-orders', 'Dashboard\VendorDashboardController@getDaywiseOrders');
        Route::get('dashboard/total-earnings', 'Dashboard\VendorDashboardController@getTotalEarnings');
        Route::get('dashboard/top-sellings', 'Dashboard\VendorDashboardController@getTopSellings');
        Route::get('dashboard/recent-orders', 'Dashboard\VendorDashboardController@getRecentOrders');
        /* Dashboard */

        /* restaurant */
        /* restaurant categories */
        Route::get('restaurants/{slug}/restaurant-categories', 'RestaurantCategoryController@getCategoriesByRestaurant');
        Route::post('restaurants/add-restaurant-categories/{slug}', 'RestaurantController@addRestaurantCategories');
        Route::post('restaurants/remove-restaurant-categories/{slug}', 'RestaurantController@removeRestaurantCategories');
        Route::put('restaurant-branches/{slug}/update', 'RestaurantBranchController@updateWithTagsAndCategories');
        Route::get('restaurant-categories', 'RestaurantCategoryController@index');
        Route::get('restaurant-tags', 'RestaurantTagController@index');

        /* menus */
        Route::get('restaurant-branches/{slug}/menus', 'MenuController@getMenusByBranch');
        Route::get('restaurant-branches/{slug}/available-menus', 'MenuController@getAvailableMenusByBranch');
        Route::get('menus/{slug}', 'MenuController@show');
        Route::post('menus', 'MenuController@store');
        Route::post('menus/import', 'MenuController@import');
        Route::put('menus/{slug}', 'MenuController@update');
        Route::patch('menus/toggle-enable/{slug}', 'MenuController@toggleEnable');
        Route::post('restaurant-branches/{restaurantBranchSlug}/menus/{slug}', 'RestaurantBranchController@toggleAvailable');

        Route::get('menu-variations/{slug}', 'MenuVariationController@show');
        Route::post('menu-variations', 'MenuVariationController@store');
        Route::put('menu-variations/{slug}', 'MenuVariationController@update');
        Route::delete('menu-variations/{slug}', 'MenuVariationController@destroy');

        Route::get('menu-variation-values/{slug}', 'MenuVariationValueController@show');
        Route::post('menu-variation-values', 'MenuVariationValueController@store');
        Route::put('menu-variation-values/{slug}', 'MenuVariationValueController@update');
        Route::delete('menu-variation-values/{slug}', 'MenuVariationValueController@destroy');

        Route::get('menu-toppings/{slug}', 'MenuToppingController@show');
        Route::post('menu-toppings', 'MenuToppingController@store');
        Route::put('menu-toppings/{slug}', 'MenuToppingController@update');
        Route::delete('menu-toppings/{slug}', 'MenuToppingController@destroy');

        Route::get('restaurant-branches/{slug}/orders', 'RestaurantOrderController@getBranchOrders');
        Route::get('restaurant-orders/{slug}', 'RestaurantOrderController@show');
        Route::post('restaurant-orders/{slug}/change-status', 'RestaurantOrderController@changeStatus');
        Route::delete('restaurant-orders/{slug}', 'RestaurantOrderController@destroy');


        Route::resource('restaurant-orders', 'RestaurantOrderController', ['as' => 'vendor']);
        /* restaurant */

        /* shop */
        /* shop categories */
        Route::resource('shops', 'ShopController', ['as' => 'vendor']);
        Route::get('shop-tags', 'ShopTagController@index');
        Route::get('shop-categories', 'ShopCategoryController@index');
        Route::get('shops/{slug}/shop-categories', 'ShopCategoryController@getCategoriesByShop');
        Route::post('shops/add-shop-categories/{slug}', 'ShopController@addShopCategories');
        Route::post('shops/remove-shop-categories/{slug}', 'ShopController@removeShopCategories');
        Route::get('shop-categories/{slug}/sub-categories', 'ShopSubCategoryController@getSubCategoriesByCategory');

        Route::resource('shop-orders', 'ShopOrderController', ['as' => 'vendor']);
        Route::get('shops/{slug}/shop-orders', 'ShopOrderController@getShopOrders');
        Route::post('shop-orders/{slug}/change-status', 'ShopOrderController@changeStatus');


        /* products */
        Route::get('shops/{slug}/products', 'ProductController@getProductsByShop');
        Route::get('products/{slug}', 'ProductController@show');
        Route::post('products', 'ProductController@store');
        Route::put('products/{slug}', 'ProductController@update');
        Route::delete('products/{slug}', 'ProductController@destroy');
        Route::patch('products/toggle-enable/{slug}', 'ProductController@toggleEnable');
        Route::post('products/import', 'ProductController@import');

        Route::get('products/{slug}/product-variations', 'ProductVariationController@getProductVariationsByProduct');
        Route::get('product-variations/{slug}', 'ProductVariationController@show');
        Route::post('product-variations', 'ProductVariationController@store');
        Route::put('product-variations/{slug}', 'ProductVariationController@update');
        Route::delete('product-variations/{slug}', 'ProductVariationController@destroy');

        Route::get('product-variation/{slug}/product-variation-values', 'ProductVariationValueController@getProductVariationValuesByProductVariation');
        Route::get('product-variation-values/{slug}', 'ProductVariationValueController@show');
        Route::post('product-variation-values', 'ProductVariationValueController@store');
        Route::put('product-variation-values/{slug}', 'ProductVariationValueController@update');
        Route::delete('product-variation-values/{slug}', 'ProductVariationValueController@destroy');

        Route::get('brands', 'BrandController@index');
        Route::post('brands', 'BrandController@store');

        Route::get('cities', 'CityController@index');
        Route::get('townships', 'TownshipController@index');
        Route::get('cities/{slug}/townships', 'TownshipController@getTownshipsByCity');

        Route::get('customers', 'CustomerController@index');
        Route::post('customers', 'CustomerController@store');

        /* Address */
        Route::get('customers/{slug}/addresses', 'AddressController@index');
        Route::post('customers/{slug}/addresses', 'AddressController@store');

        Route::get('promocodes', 'PromocodeController@index');

        /* shop */

        Route::post('/register-device', 'UserController@registerToken');
    });
});
