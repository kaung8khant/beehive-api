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
        Route::put('restaurant-branches/{restaurantBranch}/update', 'RestaurantBranchController@updateWithTagsAndCategories');
        Route::get('restaurant-categories', 'RestaurantCategoryController@index');
        Route::get('restaurant-tags', 'RestaurantTagController@index');

        /* menus */
        Route::get('restaurant-branches/{restaurantBranch}/menus', 'MenuController@getMenusByBranch');
        Route::get('restaurant-branches/{restaurantBranch}/menus-with-additionals', 'MenuController@getMenusByBranchWithAdditionals');
        Route::post('menus/status', 'MenuController@multipleStatusUpdate');
        Route::patch('menus/toggle-enable/{slug}', 'MenuController@toggleEnable');
        Route::post('menus/multiple-delete', 'MenuController@multipleDelete');
        Route::resource('menus', 'MenuController', ['except' => ['create', 'edit']]);

        Route::post('restaurant-branches/{restaurantBranch}/menus/{menu}', 'RestaurantBranchController@toggleAvailable');

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

        Route::get('restaurant-branches/{restaurantBranch}/orders', 'RestaurantOrderController@getBranchOrders');
        Route::post('restaurant-orders/{restaurantOrder}/change-status', 'RestaurantOrderController@changeStatus');
        Route::resource('restaurant-orders', 'RestaurantOrderController', ['as' => 'vendor']);
        Route::get('restaurant-branches/{restaurantBranch}/customers', 'RestaurantBranchController@getRestaurantBranchByCustomers');
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
        Route::get('shops/{shop}/shop-orders', 'ShopOrderController@getShopOrders');
        Route::post('shop-orders/{shopOrder}/change-status', 'ShopOrderController@changeStatus');
        Route::get('shops/{slug}/customers', 'ShopController@getCustomersByShop');

        /* products */
        Route::get('shops/{shop}/products', 'ProductController@getProductsByShop');

        Route::resource('products', 'ProductController', ['except' => ['create', 'edit']]);
        Route::patch('products/toggle-enable/{product}', 'ProductController@toggleEnable');
        Route::post('products/status', 'ProductController@multipleStatusUpdate');
        Route::post('products/multiple-delete', 'ProductController@multipleDelete');

        Route::get('products/{product}/product-variations', 'ProductVariationController@getProductVariationsByProduct');
        Route::get('product-variations/{slug}', 'ProductVariationController@show');
        Route::post('product-variations', 'ProductVariationController@store');
        Route::put('product-variations/{slug}', 'ProductVariationController@update');
        Route::delete('product-variations/{slug}', 'ProductVariationController@destroy');

        Route::get('product-variations/{productVariation}/product-variation-values', 'ProductVariationValueController@getVariationValuesByVariation');
        Route::get('product-variation-values/{slug}', 'ProductVariationValueController@show');
        Route::post('product-variation-values', 'ProductVariationValueController@store');
        Route::put('product-variation-values/{slug}', 'ProductVariationValueController@update');
        Route::delete('product-variation-values/{slug}', 'ProductVariationValueController@destroy');

        Route::get('brands', 'BrandController@index');
        Route::post('brands', 'BrandController@store');

        Route::get('cities', 'CityController@index');
        Route::get('townships', 'TownshipController@index');
        Route::get('cities/{city}/townships', 'TownshipController@getTownshipsByCity');

        Route::get('customers', 'CustomerController@index');
        Route::post('customers', 'CustomerController@store');

        /* Address */
        Route::get('customers/{slug}/addresses', 'AddressController@index');
        Route::post('customers/{slug}/addresses', 'AddressController@store');

        Route::get('promocodes', 'PromocodeController@index');

        /* shop */

        Route::post('/register-device', 'UserController@registerToken');

        Route::post('excels/import/{type}', 'Excel\ExportImportController@import');
        Route::get('excels/export/{type}', 'Excel\ExportImportController@export');
        Route::get('excels/export/{type}/{params}', 'Excel\ExportImportController@exportWithParams');
    });
});
